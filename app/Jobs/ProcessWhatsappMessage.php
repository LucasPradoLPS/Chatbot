<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\InstanciaWhatsapp;
use App\Models\Agente;
use App\Models\AgenteGerado;
use App\Models\Empresa;
use App\Models\MensagensMemoria;
use App\Models\IaIntervencao;
use App\Models\Thread;
use App\Services\IntentDetector;

// Aumentar timeout para 10 minutos para requisições OpenAI com polling
set_time_limit(600);
use App\Services\SlotsSchema;
use App\Services\StateMachine;
use App\Services\ContextualResponseValidator;
use App\Services\MatchingEngine;
use App\Services\SimuladorFinanciamento;
use App\Services\EventService;
use App\Services\MediaProcessor;
use App\Models\SuporteChamado;

class ProcessWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public $tries = 3;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $data = $this->data;

        $instance = $data['instance'] ?? null;
        $remetente = $data['data']['key']['remoteJid'] ?? null;
        $senderPn = $data['data']['key']['senderPn'] ?? null; // JID real do remetente (quando disponível)
        $messageId = $data['data']['key']['id'] ?? null;
        // If flag is missing, assume message is from user (not from the bot).
        $fromMe = $data['data']['key']['fromMe'] ?? false;
        $isGrupo = $remetente && str_ends_with($remetente, '@g.us');
        $source = $data['data']['source'] ?? null;
        $msgData = $data['data']['message'] ?? [];
        $pushName = $data['data']['pushName'] ?? null; // Nome do contato no WhatsApp (se disponível)

        // Deduplicação rápida: evita processar o mesmo messageId duas vezes (ack/reentrega Evolution)
        if ($messageId) {
            $dedupKey = 'whatsapp_msg_' . $messageId;
            if (!Cache::add($dedupKey, true, now()->addMinutes(10))) {
                Log::info('[BLOQUEADO] Mensagem duplicada ignorada', [
                    'message_id' => $messageId,
                    'remetente' => $remetente,
                ]);
                return;
            }
        }

        // Normaliza identificador do contato para garantir continuidade
        // - Para contatos individuais: apenas dígitos do número (estável entre @lid e @s.whatsapp.net)
        // - Para grupos: manter JID completo
        $rawId = $senderPn ?: $remetente;
        if (!$isGrupo && $rawId) {
            if (str_ends_with($rawId, '@lid')) {
                $rawId = preg_replace('/@lid$/', '@s.whatsapp.net', $rawId);
            }
        }
        $clienteId = $isGrupo
            ? ($rawId ?? $remetente)
            : preg_replace('/\D/', '', preg_replace('/@.+$/', '', ($rawId ?? $remetente)));

        // Guard rail: só prossegue se o identificador do cliente for numérico e com tamanho plausível (10-15 dígitos)
        $clienteDigits = preg_replace('/\D/', '', (string) $clienteId);
        if (!$isGrupo && (strlen($clienteDigits) < 10 || strlen($clienteDigits) > 15)) {
            Log::warning('[BLOQUEADO] Identificador de cliente inválido para envio', [
                'clienteId' => $clienteId,
                'remetente' => $remetente,
                'senderPn' => $senderPn,
            ]);
            return;
        }
        $clienteId = $isGrupo ? $clienteId : $clienteDigits;

        Log::debug('[DEBUG] Identificador normalizado do contato', [
            'remetente' => $remetente,
            'senderPn' => $senderPn,
            'isGrupo' => $isGrupo,
            'clienteId' => $clienteId,
        ]);

        Log::info('ProcessWhatsappMessage: start', [
            'instance' => $instance,
            'remetente' => $remetente,
            'senderPn' => $senderPn,
            'fromMe' => $fromMe,
            'isGrupo' => $isGrupo,
            'source' => $source,
        ]);
        Log::debug('[DEBUG] handle() iniciado');
        Log::info('[ENTRADA] Mensagem recebida de: ' . $remetente . ' | Instance: ' . $instance . ' | fromMe: ' . ($fromMe ? 'SIM' : 'NÃO') . ' | Grupo: ' . ($isGrupo ? 'SIM' : 'NÃO'));

        if (!$instance || !$remetente) {
            Log::warning('[ERRO] Dados incompletos no job ProcessWhatsappMessage');
            Log::warning('[BLOQUEADO] Instance: ' . ($instance ?? 'NULL') . ' | Remetente: ' . ($remetente ?? 'NULL'));
            return;
        }

        $instancia = InstanciaWhatsapp::where('instance_name', $instance)->first();
        if (!$instancia) {
            Log::warning('[ERRO] Instância não encontrada', ['instance' => $instance]);
            Log::warning('[BLOQUEADO] Instância N8n não existe no banco para: ' . $remetente);
            return;
        }

        // ============ VERIFICAÇÃO DE HORÁRIO DE ATENDIMENTO ============
        // Horário de atendimento: Segunda a Sexta-feira, 08h às 17h
        $agora = now('America/Sao_Paulo'); // Usar timezone de São Paulo
        $dia_semana = $agora->dayOfWeek; // 0=domingo, 1=segunda, ..., 6=sábado
        $hora_atual = $agora->hour;

        // Verificar se é fim de semana (domingo=0 ou sábado=6)
        $eh_fim_semana = $dia_semana == 0 || $dia_semana == 6;
        
        // Verificar se está fora do horário (antes das 08h ou depois das 17h)
        $fora_horario = $hora_atual < 8 || $hora_atual >= 17;

        if ($eh_fim_semana || $fora_horario) {
            Log::info('[FORA DE HORÁRIO] Mensagem recebida fora do atendimento', [
                'numero_cliente' => $clienteId,
                'dia_semana' => ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'][$dia_semana],
                'hora' => $hora_atual,
                'eh_fim_semana' => $eh_fim_semana,
                'fora_horario' => $fora_horario,
            ]);

            // Enviar mensagem de horário de atendimento
            try {
                $resposta_fora_horario = "⏰ Horário de Atendimento\n\nNosso horário de atendimento é:\n🕗 Segunda a sexta-feira, das 08h às 17h.\n\nFicaremos felizes em te atender dentro desse horário 😊";
                
                Http::withHeaders(['apikey' => config('services.evolution.key')])
                    ->post(config('services.evolution.url') . "/instances/{$instance}/send", [
                        'number' => $clienteId,
                        'text' => $resposta_fora_horario,
                        'jid' => $remetente,
                    ]);
                
                Log::info('[FORA DE HORÁRIO] Resposta enviada ao cliente', [
                    'numero_cliente' => $clienteId,
                    'remetente' => $remetente,
                ]);
            } catch (\Exception $e) {
                Log::warning('[FORA DE HORÁRIO] Erro ao enviar resposta', [
                    'numero_cliente' => $clienteId,
                    'erro' => $e->getMessage(),
                ]);
            }

            return; // Não processar a mensagem
        }
        // ============================================================

        // Configuração de autoatendimento pelo próprio operador
        $allowSelfChat = (bool) config('app.allow_self_chat');

        // Mensagens marcadas como fromMe:
        // - Se vierem de web/ios (operador humano), registramos intervenção para PAUSAR a IA
        // - Se vierem da própria IA (source unknown), apenas ignoramos para evitar loop
        if ($fromMe) {
            if (!$allowSelfChat && in_array($source, ['ios', 'web'])) {
                IaIntervencao::updateOrCreate(
                    [
                        'empresa_id' => $instancia->empresa_id,
                        'numero_cliente' => $clienteId,
                    ],
                    [
                        'intervencao_em' => now(),
                    ]
                );
                Log::info('[INTERVENCAO] Humano conversando; IA pausada por 60min.', [
                    'numero_cliente' => $clienteId,
                    'source' => $source,
                ]);
            } elseif ($source === 'unknown') {
                Log::info('[LOOP-PREVENTION] Mensagem da própria IA ignorada.');
            } else {
                Log::info('[BLOQUEADO] Mensagem fromMe ignorada (evitar auto-chat).', [
                    'instance' => $instance,
                    'numero_cliente' => $clienteId,
                    'source' => $source,
                ]);
            }
            return;
        }

        $limiteTempoSemConversa = 7; // minutos

        // Lógica de bloqueio de self-chat com limite de tempo
        if (!$allowSelfChat && $fromMe) {
            // Buscar a thread para verificar última atividade do usuário
            $thread = Thread::where('empresa_id', $instancia->empresa_id)
                ->where('numero_cliente', $remetente)
                ->first();

            $ultimaAtividadeUsuario = $thread?->ultima_atividade_usuario;
            $tempoDecorrido = $ultimaAtividadeUsuario ? now()->diffInMinutes($ultimaAtividadeUsuario) : $limiteTempoSemConversa + 1;

            // Se passaram menos de 7 minutos desde a última mensagem do usuário, bloqueia
            if ($tempoDecorrido < $limiteTempoSemConversa) {
                $minRestantes = $limiteTempoSemConversa - $tempoDecorrido;
                Log::info('Mensagem do bot bloqueada (' . $tempoDecorrido . ' min de ' . $limiteTempoSemConversa . '). Apenas ' . $minRestantes . ' min restantes. (job)');
                return;
            } else {
                Log::info('Bot pode responder: ' . $tempoDecorrido . ' minutos desde última atividade do usuário ' . $remetente . '.');
            }
        }

        $mensagem = null;
        $tipoMensagem = null;
        $mediaUrl = null;
        $mediaKey = null;
        $mimetype = null;

        if (isset($msgData['conversation'])) {
            $mensagem = $msgData['conversation'];
            $tipoMensagem = 'text';
        } elseif (isset($msgData['audioMessage'])) {
            $tipoMensagem = 'audio';
            $mediaUrl = $msgData['audioMessage']['url'] ?? null;
            $mediaKey = $msgData['audioMessage']['mediaKey'] ?? null;
            $mimetype = $msgData['audioMessage']['mimetype'] ?? null;
        } elseif (isset($msgData['imageMessage'])) {
            $tipoMensagem = 'image';
            $mediaUrl = $msgData['imageMessage']['url'] ?? null;
            $mediaKey = $msgData['imageMessage']['mediaKey'] ?? null;
            $mimetype = $msgData['imageMessage']['mimetype'] ?? null;
        } elseif (isset($msgData['videoMessage'])) {
            $tipoMensagem = 'video';
        }

        // Buscar empresa antes de processar mídia
        $empresa = Empresa::find($instancia->empresa_id);
        if (!$empresa) {
            Log::warning('[ERRO] Empresa não encontrada', ['empresa_id' => $instancia->empresa_id]);
            Log::warning('[BLOQUEADO] Empresa ID ' . $instancia->empresa_id . ' não existe para: ' . $remetente);
            return;
        }

        // Criar ou buscar thread para poder processar mídia
        $thread = Thread::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $clienteId)
            ->where('updated_at', '>=', now()->subHours(48))
            ->first();

        if (!$thread) {
            // Cria nova thread se não existir
            $threadResponse = Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post('https://api.openai.com/v1/threads', []);

            $threadId = $threadResponse['id'] ?? null;

            $thread = Thread::create([
                'empresa_id' => $empresa->id,
                'numero_cliente' => $clienteId,
                'thread_id' => $threadId,
                'estado_atual' => 'STATE_START',
                'estado_historico' => []
            ]);

            Log::info('[THREAD] Criada nova thread para mídia', [
                'cliente' => $clienteId,
                'thread_id' => $threadId,
            ]);
        }

        // Processar mídias com agente inteligente
        if (in_array($tipoMensagem, ['image', 'audio', 'video', 'document'])) {
            $this->processarMedia($tipoMensagem, $msgData, $instance, $remetente, $thread, $clienteId);
            return;
        }

        // Dedup por conteúdo quando Evolution não envia messageId (evita múltiplas respostas na mesma msg)
        if (!$messageId && $mensagem) {
            $fingerprint = strtolower(trim($mensagem));
            $dedupKeyContent = 'whatsapp_msg_body_' . md5($clienteId . '|' . $fingerprint);

            if (!Cache::add($dedupKeyContent, true, now()->addSeconds(90))) {
                Log::info('[BLOQUEADO] Mensagem duplicada por conteúdo ignorada', [
                    'remetente' => $remetente,
                    'cliente' => $clienteId,
                ]);
                return;
            }
        }

        // Bloquear mensagens vazias (que não sejam texto)
        if (!$mensagem) {
            Log::info('[BLOQUEADO] Mensagem sem conteúdo de texto recebida', [
                'tipo' => $tipoMensagem,
                'cliente' => $clienteId,
            ]);
            return;
        }

        // $empresa já foi buscada antes para processar mídia
        $agente = Agente::where('empresa_id', $empresa->id)->first();

        if (!$agente || !$agente->ia_ativa) {
            Log::info('[BLOQUEADO] IA desativada para a empresa ' . $empresa->id . ' | Agente: ' . ($agente ? 'EXISTE' : 'NÃO EXISTE') . ' de: ' . $remetente);
            return;
        }

        // Ignorar sempre mensagens de grupo conforme requisito
        if ($isGrupo) {
            Log::info('[BLOQUEADO] Mensagem de grupo ignorada para empresa ' . $empresa->id . ' de: ' . $remetente);
            return;
        }

        // Considerar variações do identificador para intervenção humana
        $idsIntervencao = array_values(array_unique(array_filter([
            $clienteId,
            $remetente,
            $senderPn,
        ])));
        $intervencao = IaIntervencao::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $clienteId)
            ->where('intervencao_em', '>=', now()->subMinutes(60))
            ->first();

        if ($intervencao) {
            Log::info('[BLOQUEADO] IA pausada por intervenção humana.', [
                'numero_cliente' => $clienteId,
                'intervencao_em' => $intervencao->intervencao_em,
            ]);
            return;
        }

        $limite = $empresa->memoria_limite ?? 4;

        MensagensMemoria::create([
            'empresa_id' => $empresa->id,
            'numero_cliente' => $clienteId,
            'mensagem' => $mensagem ?? '[imagem recebida]',
            'tipo' => $tipoMensagem,
        ]);

        // Manter APENAS as últimas $limite mensagens na memória.
        MensagensMemoria::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $clienteId)
            ->orderByDesc('created_at')
            ->skip($limite)
            ->take(PHP_INT_MAX)
            ->delete();

        $promptGerado = AgenteGerado::where('empresa_id', $empresa->id)
            ->where('funcao', 'atendente_ia')
            ->orderByDesc('id')
            ->first();

        if (!$promptGerado) {
            Log::warning('[ERRO] Prompt da IA não encontrado para empresa ' . $empresa->id . ' de: ' . $remetente);
            Log::warning('[BLOQUEADO] Nenhum agente gerado com assistente para: ' . $remetente);
            return;
        }

        $assistantId = $promptGerado->agente_base_id;

        try {
            Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/instances/{$instance}/client/action/send-typing", [
                    'jid' => $remetente,
                ]);

            // Buscar thread existente (janela de 48 horas para manter contexto mais longo)
            // Buscar thread existente com identificador normalizado (48h)
            $thread = Thread::where('empresa_id', $empresa->id)
                ->where('numero_cliente', $clienteId)
                ->where('updated_at', '>=', now()->subHours(48))
                ->first();

            if (!$thread) {
                // Fallback: procurar por variações antigas do remetente e consolidar em clienteId normalizado
                $numeroExtracted = preg_replace('/\D/', '', preg_replace('/@.+$/', '', $remetente));
                $candidateKeys = array_values(array_unique(array_filter([
                    $clienteId,
                    $numeroExtracted,
                ])));
                
                // Limpar registros antigos duplicados com IDs não-normalizados (exceto o mais recente)
                $threadAntiga = Thread::where('empresa_id', $empresa->id)
                    ->whereIn('numero_cliente', $candidateKeys)
                    ->orderByDesc('updated_at')
                    ->first();

                if ($threadAntiga) {
                    // Remover duplicatas mais antigas
                    Thread::where('empresa_id', $empresa->id)
                        ->whereIn('numero_cliente', $candidateKeys)
                        ->where('id', '!=', $threadAntiga->id)
                        ->delete();
                }

                // Criar nova thread na OpenAI
                $threadResponse = Http::withToken(config('services.openai.key'))
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->post('https://api.openai.com/v1/threads', []);

                $threadId = $threadResponse['id'] ?? null;

                Log::info('[THREAD] ' . ($threadAntiga ? 'Renovando' : 'Criando nova') . ' thread', [
                    'cliente' => $clienteId,
                    'thread_id' => $threadId,
                    'antiga_id' => $threadAntiga?->thread_id,
                ]);

                // Remover qualquer thread existente com a chave normalizada (previne conflito de constraint)
                Thread::where('empresa_id', $empresa->id)
                    ->where('numero_cliente', $clienteId)
                    ->delete();

                // Detectar saudação do cliente para responder de forma adequada
                $saudacaoCliente = null;
                $mensagemLower = strtolower(trim($mensagem));
                if (preg_match('/^(oi|olá|ola|oie|oii|oiii|olaa|hey|opa|e ai|e aí)[\s\!]*$/i', $mensagemLower)) {
                    $saudacaoCliente = preg_match('/^ol[aá]/i', $mensagemLower) ? 'Olá' : 'Oi';
                    Log::info('[SAUDACAO] Detectada saudação inicial do cliente', [
                        'cliente' => $clienteId,
                        'saudacao' => $saudacaoCliente,
                    ]);
                }

                // Criar novo registro no banco com chave normalizada
                $thread = Thread::create([
                    'empresa_id' => $empresa->id,
                    'numero_cliente' => $clienteId,
                    'thread_id' => $threadId,
                    'ultima_atividade_usuario' => !$fromMe ? now() : null,
                    'slots' => [],
                    'etapa_fluxo' => 'boas_vindas',
                    'objetivo' => null,
                    'lgpd_consentimento' => false,
                    'intent' => 'indefinido',
                    'estado_atual' => 'STATE_START',
                    'estado_historico' => [],
                    'saudacao_inicial' => $saudacaoCliente,
                ]);
                
                Log::info('[THREAD] Thread criada/consolidada com sucesso', [
                    'cliente' => $clienteId,
                    'thread_id' => $thread->thread_id,
                    'saudacao_inicial' => $saudacaoCliente,
                ]);
            } else {
                $threadId = $thread->thread_id;
                $thread->touch();
                
                Log::info('[THREAD] Reutilizando thread existente', [
                    'cliente' => $clienteId,
                    'thread_id' => $threadId,
                    'idade_horas' => now()->diffInHours($thread->updated_at),
                ]);
                
                // Atualizar última atividade do usuário se a mensagem for dele
                if (!$fromMe) {
                    $thread->update(['ultima_atividade_usuario' => now()]);
                }

                // ⭐ DETECÇÃO DE REINÍCIO: Se a mensagem começar com saudação, volta para o menu inicial
                $msgLowerReinicio = strtolower(trim($mensagem));
                $ehSaudacao = preg_match('/^(oi|olá|ola|hey|opa|e aí|e ai|tudo bem|bom dia|boa tarde|boa noite|alô|alá|oie|oii)/i', $msgLowerReinicio);
                
                if ($ehSaudacao) {
                    // Resetar para o menu inicial
                    $thread->update([
                        'etapa_fluxo' => 'boas_vindas',
                        'objetivo' => null,
                        'slots' => [],
                        'intent' => 'indefinido',
                        'estado_atual' => 'STATE_START',
                    ]);
                    $thread->refresh();
                    
                    // RESPONDER DIRETO COM O MENU (sem chamar OpenAI)
                    $nomeCliente = $pushName ? trim($pushName) : "Visitante";
                    $saudacao = $thread->saudacao_inicial ?? 'Olá';
                    $respostaMenu = "{$saudacao}! {$nomeCliente} 👋 Como posso te ajudar?\n\n" .
                        "1️⃣ Comprar imóvel\n" .
                        "2️⃣ Alugar imóvel\n" .
                        "3️⃣ Documentos\n" .
                        "4️⃣ Opções de pagamento\n" .
                        "5️⃣ Pagamentos\n" .
                        "6️⃣ Nota fiscal\n" .
                        "7️⃣ Falar com corretor\n" .
                        "8️⃣ Encerrar\n\n" .
                        "Digite o número da opção desejada (1-8).";
                    
                    Log::info('[MENU] Saudação detectada, respondendo com menu direto', [
                        'numero_cliente' => $clienteId,
                        'mensagem' => $mensagem,
                        'msg_lower' => $msgLowerReinicio,
                    ]);
                    
                    // Enviar resposta e retornar (não processar mais nada)
                    try {
                        Http::withHeaders(['apikey' => config('services.evolution.key')])
                            ->post(config('services.evolution.url') . "/message/sendText/{$instance}", [
                                'number' => $clienteId,
                                'text' => $respostaMenu,
                            ]);
                        Log::info('[MENU] Resposta enviada com sucesso', ['numero_cliente' => $clienteId]);
                    } catch (\Exception $e) {
                        Log::warning('[MENU] Erro ao enviar resposta', ['erro' => $e->getMessage()]);
                    }
                    return; // NÃO continuar processando
                }


            }

            $slotsAtuais = $thread?->slots ?? [];
            if (!is_array($slotsAtuais)) {
                $slotsAtuais = json_decode((string) $thread?->slots, true) ?: [];
            }

            // Garantir que objetivo esteja definido antes de usar
            $objetivo = $thread?->objetivo ?? null;

            if (empty($slotsAtuais)) {
                // Inicializar slots baseado no objetivo atual ou dados básicos do lead
                $slotsAtuais = SlotsSchema::getSlotsByObjetivo($objetivo);

                $thread->slots = $slotsAtuais;
                $thread->crm_status = 'novo_lead';
                $thread->ultimo_contato = now();
                $thread->lgpd_consentimento_data = $thread->lgpd_consentimento ? now() : null;
                $thread->save();
                // Registrar evento: lead criado
                EventService::leadCreated($empresa->id, $clienteId, [
                    'objetivo' => $objetivo,
                    'primeira_mensagem' => $mensagem,
                ]);

                Log::info('[SLOTS] Inicializados conforme objetivo', [
                    'numero_cliente' => $clienteId,
                    'objetivo' => $objetivo,
                    'slots_count' => count($slotsAtuais),
                ]);
            }

            // Capturar estado do fluxo
            $etapaFluxo = $thread?->etapa_fluxo ?? 'boas_vindas';
            $lgpdConsentimento = $thread?->lgpd_consentimento ?? false;

            // Capturar estado atual (state machine)
            $estadoAtual = $thread?->estado_atual ?? 'STATE_START';
            $estadoHistorico = $thread?->estado_historico ?? [];

            // Detectar intenção do usuário
            $intentAtual = IntentDetector::detect($mensagem);
            $thread->intent = $intentAtual;
            $thread->save();
            // Resetar contador de fallback se a intenção for conhecida
            if ($intentAtual !== 'indefinido') {
                if (($thread->fallback_tentativas ?? 0) > 0) {
                    $thread->fallback_tentativas = 0;
                    $thread->save();
                }
            }

            Log::info('[INTENT] Detectada intenção', [
                'numero_cliente' => $clienteId,
                'intent' => $intentAtual,
                'estado_atual' => $estadoAtual,
                'mensagem' => $mensagem,
            ]);

            // Atualizar CRM status para qualificado se tem dados obrigatórios
            if (!empty($slotsAtuais['nome']) && !empty($slotsAtuais['telefone_whatsapp']) && $thread->crm_status === 'novo_lead') {
                $thread->crm_status = 'qualificado';
                $thread->ultimo_contato = now();
                $thread->proximo_followup = now()->addHours(2);
                $thread->save();
                Log::info('[CRM] Status atualizado para qualificado', ['numero_cliente' => $clienteId]);
            }

            // Validação Contextual: Verificar se resposta é válida para o estado atual
            $validacaoContextual = ContextualResponseValidator::validate($estadoAtual, $mensagem);
            
            if ($validacaoContextual['is_valid'] === false && in_array($estadoAtual, ['STATE_OBJETIVO', 'STATE_Q2_TIPO', 'STATE_Q3_QUARTOS', 'STATE_LGPD', 'STATE_PROPOSTA'])) {
                Log::warning('[VALIDACAO] Resposta inválida para estado', [
                    'numero_cliente' => $clienteId,
                    'estado' => $estadoAtual,
                    'resposta' => $mensagem,
                    'motivo' => $validacaoContextual['motivo'],
                    'opcoes_esperadas' => $validacaoContextual['opcoes_esperadas'] ?? [],
                ]);
                
                $opcoesDirecoes = ContextualResponseValidator::getValidOptionsForState($estadoAtual);
                $descricaoEsperada = ContextualResponseValidator::getExpectedAnswerDescription($estadoAtual);
                
                $respostaValidacao = match($estadoAtual) {
                    'STATE_OBJETIVO' => "Entendi, mas preciso que você escolha uma das opções:\n\n1️⃣ *Comprar* imóvel\n2️⃣ *Alugar* imóvel\n3️⃣ *Vender* meu imóvel\n4️⃣ *Anunciar* para aluguel\n5️⃣ *Investimento*\n6️⃣ *Suporte* (já sou cliente)\n7️⃣ *Falar com corretor*\n\nQual é sua intenção? 😊",
                    'STATE_Q2_TIPO' => "Desculpe, preciso que você escolha o tipo de imóvel:\n\n- Apartamento 🏢\n- Casa 🏠\n- Comercial 🏪\n- Terreno 🌳\n- Kitnet 🏘️\n\nQual é o tipo?",
                    'STATE_Q3_QUARTOS' => "Entendi! Poderia informar quantos quartos?\n\nExemplos: \"2 quartos\", \"3q\", \"1 quarto\"",
                    'STATE_LGPD' => "Preciso que você confirme: Você aceita nossa política de privacidade?\n\nResponda: *Sim* ou *Não*",
                    'STATE_PROPOSTA' => "Qual forma de pagamento você prefere?\n\n- À vista 💰\n- Financiamento 🏦\n- Parcelado 📅\n- Consórcio 📝\n- FGTS 📋\n- Permuta 🔄\n- Misto 🔀",
                    default => "Desculpe, não entendi. Poderia tentar novamente?\n\nEsperado: $descricaoEsperada"
                };
                
                $respostLimpa = $respostaValidacao;
                
                // Registrar tentativa de resposta inválida
                if (!isset($thread->fallback_tentativas)) {
                    $thread->fallback_tentativas = 0;
                }
                $thread->fallback_tentativas++;
                $thread->save();
                
                // Se 3+ tentativas, oferecer handoff
                if ($thread->fallback_tentativas >= 3) {
                    $respostLimpa .= "\n\n📞 Parece que há alguma dificuldade. Deseja *falar com um corretor*?";
                }
                
                Log::info('[VALIDACAO] Resposta de validação enviada', [
                    'numero_cliente' => $clienteId,
                    'estado' => $estadoAtual,
                ]);
                
                // Usar a resposta de validação como resposta final e prosseguir para envio
                $respostaLimpa = $respostLimpa;
                $respostaBruta = $respostLimpa;
            }

            // Se passou na validação, atualizar slots se há correspondência
            if ($validacaoContextual['is_valid'] === true && isset($validacaoContextual['slot'])) {
                $slotsAtuais = ContextualResponseValidator::updateSlotsFromValidation($slotsAtuais, $validacaoContextual);
                $thread->slots = json_encode($slotsAtuais, JSON_UNESCAPED_UNICODE);
                $thread->save();
                Log::info('[SLOTS] Atualizados por validação contextual', [
                    'numero_cliente' => $clienteId,
                    'slot' => $validacaoContextual['slot'],
                    'valor' => $validacaoContextual['valor_slot'],
                ]);
            }

            // Detectar e validar próximo estado
            $proximoEstado = StateMachine::detectNextState($estadoAtual, $intentAtual, $objetivo);
            if ($proximoEstado && StateMachine::isValidTransition($estadoAtual, $proximoEstado)) {
                // Registrar transição

                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximoEstado);
                $thread->estado_atual = $proximoEstado;
                $thread->estado_historico = $estadoHistorico;
                $thread->save();

                Log::info('[STATE-MACHINE] Transição de estado', [
                    'numero_cliente' => $clienteId,
                    'de' => $estadoAtual,
                    'para' => $proximoEstado,
                ]);

                $estadoAtual = $proximoEstado;
                // Se transicionou para REFINAR, incrementar ciclo
                if ($proximoEstado === 'STATE_REFINAR') {
                    $thread->refino_ciclos = ($thread->refino_ciclos ?? 0) + 1;
                    $thread->save();
                    Log::info('[REFINO] Ciclo incrementado', [
                        'numero_cliente' => $clienteId,
                        'refino_ciclos' => $thread->refino_ciclos,
                    ]);
                }
            }

            // Enviar a mensagem atual acompanhada de um pequeno resumo das últimas interações
            // para reforçar continuidade sem enviar todo o histórico
            $ultimasMemorias = MensagensMemoria::where('empresa_id', $empresa->id)
                ->where('numero_cliente', $clienteId)
                ->orderByDesc('created_at')
                ->take($limite)
                ->get()
                ->reverse();

            $resumo = [];
            foreach ($ultimasMemorias as $m) {
                $texto = $m->mensagem;
                if ($m->tipo && $m->tipo !== 'text') {
                    $texto = '[' . $m->tipo . ']';
                }
                $resumo[] = $texto;
            }

            $textoContexto = '';
            if (!empty($resumo)) {
                $textoContexto = "Contexto recente (últimas interações):\n- " . implode("\n- ", $resumo) . "\n\n";
            }

            $textoMensagemAtual = $mensagem ?? '[imagem recebida]';

            $textoSlots = 'Slots atuais (JSON): ' . json_encode($slotsAtuais, JSON_UNESCAPED_UNICODE);
            $regrasSlots = "Regras de coleta com slots:\n- Pergunte apenas uma coisa por vez e espere a resposta.\n- Seja consultivo: ofereça 3 a 8 opções iniciais (curtas) e refine com novas perguntas conforme as respostas, sempre atualizando slots.\n- Atualize e devolva sempre o estado COMPLETO dos slots em JSON no bloco [[SLOTS]]{...}[[/SLOTS]].\n- Se um slot ainda não foi respondido, mantenha-o com valor null.\n- Slots obrigatórios (nunca deixe null): nome, telefone_whatsapp, cidade, preferencia_contato.\n- Slots opcionais podem permanecer null: email, banheiros, metragem_min, condominio_max, fotos_link.\n- Só faça uma nova pergunta se ainda houver slot vazio E relevante para o objetivo.\n- Se todos os slots OBRIGATÓRIOS estiverem preenchidos, confirme o resumo.\n- SEMPRE encerre cada etapa com um próximo passo CLARO e explícito.";

            // Descrição da intenção detectada
            $descricaoIntent = IntentDetector::describe($intentAtual);
            $textoIntent = "Intenção detectada: $intentAtual.\n$descricaoIntent\n\n";

            // Prompt da máquina de estados
            $promptEstado = StateMachine::getPrompt($estadoAtual);
            $descricaoEstado = StateMachine::describe($estadoAtual);
            $textoEstado = "Estado atual: $estadoAtual ($descricaoEstado).\nInstruções para este estado:\n$promptEstado\n\n";

            // RE-LER etapa do fluxo do banco (pode ter sido atualizada pelas transições acima)
            $thread->refresh();
            $etapaFluxo = $thread->etapa_fluxo ?? 'boas_vindas';

            // Instruções por etapa do fluxo
            $saudacaoInicial = $thread->saudacao_inicial ?? 'Olá';
            $instrucoesFluxo = match($etapaFluxo) {
                'boas_vindas' => "ETAPA: Menu principal.\nResponda EXATAMENTE com este menu, sem adicionar explicações extras:\n\n" .
                    "{$saudacaoInicial}! " . ($pushName ? trim($pushName) : "Visitante") . " 👋 Como posso te ajudar?\n\n" .
                    "1️⃣ Comprar imóvel\n" .
                    "2️⃣ Alugar imóvel\n" .
                    "3️⃣ Documentos\n" .
                    "4️⃣ Opções de pagamento\n" .
                    "5️⃣ Pagamentos\n" .
                    "6️⃣ Nota fiscal\n" .
                    "7️⃣ Falar com corretor\n" .
                    "8️⃣ Encerrar\n\n" .
                    "Digite o número da opção desejada (1-8).",
                'lgpd' => "ETAPA: Consentimento LGPD.\nSua tarefa: pergunte ao usuário se ele consente em compartilhar dados pessoais para melhor atendimento e em conformidade com a LGPD.\nAceite: 'sim', 'concordo', 'aceito', 'claro', etc.\nDepois de confirmado, mover para etapa 'objetivo'.\nPróximo: identificar objetivo.",
                'objetivo' => "ETAPA: Identificar objetivo do usuário.\nOfereça exatamente estas 6 opções de forma clara:\n1️⃣ Comprar imóvel\n2️⃣ Alugar imóvel\n3️⃣ Vender imóvel\n4️⃣ Anunciar para aluguel (proprietário)\n5️⃣ Investimento imobiliário\n6️⃣ Falar com corretor (atendimento humano)\nEspere o usuário escolher uma opção.\nDepois de selecionado, capturar objetivo e mover para etapa 'qualificacao'.",
                'qualificacao' => "ETAPA: Qualificação (dados do lead + preferências).\nColeta DADOS DO LEAD (obrigatórios): nome, telefone_whatsapp, cidade, preferencia_contato, melhor_horario_contato.\nDepois colete dados específicos conforme objetivo:\n- Se COMPRA/ALUGUEL: tipo_imovel, finalidade, bairro_regiao, faixa_valor_min/max, quartos, vagas, prazo_mudanca, entrada_disponivel, aprovacao_credito, etc.\n- Se CAPTAÇÃO: endereco_imovel, tipo_imovel, quartos, area_total, estado_imovel, urgencia_venda_locacao, preco_desejado, fotos_link, etc.\nSeja consultivo: ofereça 3-8 opções e refine conforme respostas.\n\nMensagem pronta de filtro (use agora para direcionar a coleta):\n" .
                    "Para eu acertar nas opções: qual bairro/região e valor máximo você tem em mente?\n" .
                    "\nDepois de qualificado (dados obrigatórios completos), mover para etapa apropriada.",
                'catalogo' => "ETAPA: Catálogo e recomendação.\nApresente imóveis que combinam com o perfil do usuário (match baseado nos slots).\nMostre como cards curtos com: preço, localização, quartos, tipo, área.\nOfereça filtros rápidos: por preço, localização, tipo.\nPermita: ver mais detalhes, agendar visita, salvar favorito.\n\nConfirmação (com base nos slots coletados):\nDiga: 'Perfeito: [bairro/região], até R$ [faixa_valor_max], [quartos] quartos, [vagas] vaga(s). Está correto?'\nSubstitua os colchetes pelos slots atuais (se algum estiver vazio, peça educadamente).\n\nFechamento com CTA:\n" .
                    "Quer que eu te mostre as melhores opções agora ou prefere agendar um papo rápido com um corretor?\n" .
                    "\nSe interesse por visita: mover para etapa 'agendamento'.",
                'agendamento' => "ETAPA: Agendamento de visita.\nPergunte datas e horários disponíveis. Ofereça 3-5 opções.\nConfirme: data, horário, imóvel, endereço.\nOfereça confirmação por SMS/WhatsApp e lembrete antes da visita.\nDepois confirmado, mover para etapa 'pos_atendimento'.",
                'proposta' => "ETAPA: Proposta / Simulação / Documentos.\nApresente simulação de financiamento (se compra/aluguel baseado no aprovacao_credito e entrada_disponivel).\nOfereça proposta formal com condições, prazos, valores.\nFornecimento de documentos necessários (checklist baseado nos slots: IPTU, RG, comprovante renda, etc).\nPróximos passos: assinatura digital, aprovação, contratação.",
                'pos_atendimento' => "ETAPA: Pós-atendimento (Follow-up).\nAgradeça pela participação na visita ou interação.\nPergunte feedback: o que achou? Tem dúvidas? Quer outras opções?\nOfereça follow-up: novas sugestões, contato com corretor, agendamento de nova visita.\nManter relacionamento ativo e consultivo.",
                'captacao' => "ETAPA: Captação (para quem quer vender/anunciar).\nColeta dados DO IMÓVEL: endereco_imovel, tipo_imovel, quartos, vagas, area_total, estado_imovel, urgencia_venda_locacao, preco_desejado, fotos_link, esta_ocupado, melhor_horario_visita_captacao.\nInformações: avaliação de mercado, comissão, exclusividade, permuta (se venda).\nDocumentação: verificar tem_documentacao_ok, solicitar IPTU, RG do proprietário.\nOfereça análise gratuita e valorização da propriedade.",
                'suporte' => "ETAPA: Suporte (pós-contratação).\nOfereça informações sobre: status de proposta, contrato, boletos de aluguel, manutenção.\nFornecimento de documentos, esclareça dúvidas sobre prazos.\nCanal de suporte sempre disponível para questões técnicas.",
                'handoff' => "ETAPA: Handoff para humano.\nTransfira para um corretor de forma profissional.\nPassar contexto: objetivo, dados coletados (slots), dúvidas específicas.\nOfereça agendamento de ligação com corretor especializado.\nMensagem calorosa para garantir transição suave.",
                default => "ETAPA desconhecida. Retorne à etapa 'boas_vindas'.",
            };

            $textoContextoFluxo = "Marca: {$empresa->nome}\nNome do cliente: " . ($pushName ? trim($pushName) : 'não informado') . "\nEstado do fluxo: etapa=$etapaFluxo, objetivo=$objetivo, lgpd_consentido=" . ($lgpdConsentimento ? 'sim' : 'não') . ".\n\n" . $instrucoesFluxo . "\n\n";

            $conteudoAtual = [
                ['type' => 'text', 'text' => $textoContextoFluxo . $textoEstado . $textoIntent . $textoContexto . $textoSlots . "\n\n" . $regrasSlots . "\n\nMensagem do cliente: " . $textoMensagemAtual]
            ];

            Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                    'role' => 'user',
                    'content' => $conteudoAtual,
                ]);

            $runResponseObj = Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
                    'assistant_id' => $assistantId,
                ]);

            $runResponse = $runResponseObj->json();
            $runId = $runResponse['id'] ?? null;
            
            // Verificar se criação da run falhou
            if (!$runId) {
                Log::error('Falha ao criar run na OpenAI', [
                    'http_status' => $runResponseObj->status(),
                    'response' => $runResponse,
                    'assistant_id' => $assistantId,
                    'thread_id' => $threadId,
                ]);
                throw new \RuntimeException('Falha ao criar run: ' . ($runResponse['message'] ?? 'resposta vazia'));
            }
            
            Log::info('Run criada com sucesso', [
                'run_id' => $runId,
                'assistant_id' => $assistantId,
            ]);

            // Polling otimizado: aguarda conclusão da IA com timeout aumentado
            $tentativas = 0;
            $maxTentativas = 120; // máximo 120 segundos para aguardar resposta da OpenAI
            $tentativasFailed = 0;
            $maxTentativasFailed = 5; // Máximo de falhas de conexão antes de desistir
            do {
                usleep(1000000); // 1 segundo entre checks
                try {
                    $apiKey = config('services.openai.key');
                    $endpointUrl = "https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}";
                    
                    $statusResponse = Http::timeout(30)->withToken($apiKey)
                        ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                        ->get($endpointUrl);
                    
                    // Ensure status is an array
                    $statusArray = $statusResponse->json();
                    $status = is_array($statusArray) ? $statusArray : (array) $statusArray;
                    $tentativas++;
                    $tentativasFailed = 0;
                    
                    $statusValue = $status['status'] ?? 'unknown';
                    
                    // Log detalhado nas primeiras tentativas para debug
                    if ($tentativas <= 3) {
                        Log::debug('Status da IA na tentativa ' . $tentativas, [
                            'status' => $statusValue,
                            'http_status' => $statusResponse->status(),
                            'url' => $endpointUrl,
                            'api_key_prefix' => substr($apiKey, 0, 20) . '...',
                            'response_keys' => array_keys($status),
                        ]);
                    }
                } catch (\Exception $e) {
                    $tentativasFailed++;
                    Log::warning('Erro ao verificar status da IA', ['erro' => $e->getMessage(), 'tentativa' => $tentativasFailed]);
                    if ($tentativasFailed >= $maxTentativasFailed) {
                        throw $e;
                    }
                    continue;
                }
            } while (($status['status'] ?? null) !== 'completed' && $tentativas < $maxTentativas);

            if ($tentativas >= $maxTentativas) {
                Log::error('Timeout aguardando resposta da OpenAI após ' . $maxTentativas . ' segundos');
                throw new \RuntimeException('Timeout na resposta da IA (aguardou ' . $maxTentativas . 's)');
            }

            $messagesResponse = Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->get("https://api.openai.com/v1/threads/{$threadId}/messages");
            
            // Ensure messages is an array
            $messagesArray = $messagesResponse->json();
            $messages = is_array($messagesArray) ? $messagesArray : (array) $messagesArray;

            $respostaBruta = $messages['data'][0]['content'][0]['text']['value'] ?? 'Desculpe, não consegui responder.';

            $slotsExtraidos = null;
            $respostaLimpa = $respostaBruta;
            if (preg_match('/\[\[SLOTS\]\](\{.*\})\[\[\/SLOTS\]\]/s', $respostaBruta, $slotsMatch)) {
                $jsonSlots = trim($slotsMatch[1]);
                $slotsDecodificados = json_decode($jsonSlots, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $slotsExtraidos = $slotsDecodificados;
                    $thread->slots = $slotsExtraidos;
                    $thread->save();

                    Log::info('[SLOTS] Estado atualizado na thread', [
                        'thread_id' => $threadId,
                        'slots' => $slotsExtraidos,
                    ]);
                } else {
                    Log::warning('[SLOTS] Falha ao decodificar JSON de slots', [
                        'thread_id' => $threadId,
                        'json' => $jsonSlots,
                        'error' => json_last_error_msg(),
                    ]);
                }

                $respostaLimpa = trim(str_replace($slotsMatch[0], '', $respostaBruta));
            }

            // Atualizar visão local dos slots após possível atualização do assistant
            $slotsAtuais = is_array($thread->slots) ? $thread->slots : (json_decode((string)$thread->slots, true) ?: []);

            // Regras de avanço para o fluxo de agendamento de visita
            try {
                if (in_array($estadoAtual, [
                    'STATE_VISITA_IMOVEL_ESCOLHA',
                    'STATE_VISITA_DATA_HORA',
                    'STATE_VISITA_CONFIRMACAO',
                    'STATE_VISITA_POS',
                ])) {
                    $estadoHistorico = $thread->estado_historico ?? [];

                    // Passo 1: escolher imóvel
                    if ($estadoAtual === 'STATE_VISITA_IMOVEL_ESCOLHA') {
                        // Tentar extrair um código do texto (#123 ou 123)
                        $codigoEscolhido = null;
                        if (preg_match('/#(\d{1,8})/', $mensagem, $m)) {
                            $codigoEscolhido = $m[1];
                        } elseif (preg_match('/\b(\d{1,8})\b/', $mensagem, $m)) {
                            $codigoEscolhido = $m[1];
                        }

                        if ($codigoEscolhido) {
                            $slotsAtuais['imovel_codigo_escolhido'] = $codigoEscolhido;
                            $thread->slots = $slotsAtuais;
                            $thread->save();
                        }

                        if (!empty($slotsAtuais['imovel_codigo_escolhido'])) {
                            $proximo = 'STATE_VISITA_DATA_HORA';
                            if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                $thread->estado_atual = $proximo;
                                $thread->estado_historico = $estadoHistorico;
                                $thread->save();
                                $estadoAtual = $proximo;
                                Log::info('[AGENDAMENTO] Imóvel escolhido e transicionado para DATA/HORA', [
                                    'numero_cliente' => $clienteId,
                                    'codigo' => $slotsAtuais['imovel_codigo_escolhido'],
                                ]);
                            }
                        }
                    }

                    // Passo 2: data e hora
                    if ($estadoAtual === 'STATE_VISITA_DATA_HORA') {
                        $temDataHora = (!empty($slotsAtuais['visita_data']) && !empty($slotsAtuais['visita_hora'])) || !empty($slotsAtuais['visita_datetime']);
                        if ($temDataHora) {
                            $proximo = 'STATE_VISITA_CONFIRMACAO';
                            if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                $thread->estado_atual = $proximo;
                                $thread->estado_historico = $estadoHistorico;
                                $thread->save();
                                $estadoAtual = $proximo;
                                Log::info('[AGENDAMENTO] Data/hora coletadas, indo para CONFIRMAÇÃO', [
                                    'numero_cliente' => $clienteId,
                                ]);
                            }
                        }
                    }

                    // Passo 3: confirmação
                    if ($estadoAtual === 'STATE_VISITA_CONFIRMACAO') {
                        $confirmado = false;
                        if (!empty($slotsAtuais['visita_confirmada']) && preg_match('/^(sim|ok|confirmado|confirmo)$/i', (string)$slotsAtuais['visita_confirmada'])) {
                            $confirmado = true;
                        } elseif (preg_match('/\b(sim|confirmo|confirmado|ok)\b/i', $mensagem)) {
                            $confirmado = true;
                            $slotsAtuais['visita_confirmada'] = 'sim';
                            $thread->slots = $slotsAtuais;
                            $thread->save();
                        }

                        if ($confirmado) {
                            $proximo = 'STATE_VISITA_POS';
                            if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                $thread->estado_atual = $proximo;
                                $thread->estado_historico = $estadoHistorico;
                                $thread->save();
                                $estadoAtual = $proximo;
                                Log::info('[AGENDAMENTO] Visita confirmada, indo para PÓS-VISITA', [
                                    'numero_cliente' => $clienteId,
                                ]);
                            }
                        }
                    }

                    // Passo 4: pós-visita (opcionalmente encaminhar de volta para catálogo)
                    if ($estadoAtual === 'STATE_VISITA_POS') {
                        if (!empty($slotsAtuais['pos_visita_feedback'])) {
                            $fb = strtolower((string)$slotsAtuais['pos_visita_feedback']);
                            if (in_array($fb, ['nao', 'não', 'talvez'])) {
                                $proximo = 'STATE_MATCH_RESULT';
                                if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                    $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                    $thread->estado_atual = $proximo;
                                    $thread->estado_historico = $estadoHistorico;
                                    $thread->save();
                                    $estadoAtual = $proximo;
                                    Log::info('[AGENDAMENTO] Pós-visita: retornando ao catálogo para novas opções', [
                                        'numero_cliente' => $clienteId,
                                    ]);
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[AGENDAMENTO] Erro ao processar avanço do fluxo de visita', [
                    'erro' => $e->getMessage(),
                ]);
            }

            // Regras de avanço para o fluxo de PROPOSTA
            try {
                if ($estadoAtual === 'STATE_PROPOSTA') {
                    $estadoHistorico = $thread->estado_historico ?? [];

                    // Tentar extrair código do imóvel (#123 ou 123)
                    $codigoImoveiProposta = null;
                    if (preg_match('/#(\d{1,8})/', $mensagem, $m)) {
                        $codigoImoveiProposta = $m[1];
                    } elseif (preg_match('/\b(\d{1,8})\b/', $mensagem, $m)) {
                        $codigoImoveiProposta = $m[1];
                    }

                    if ($codigoImoveiProposta) {
                        $slotsAtuais['imovel_proposta_codigo'] = $codigoImoveiProposta;
                        $thread->slots = $slotsAtuais;
                        $thread->save();
                    }

                    // Verificar se todos os dados mínimos de proposta foram coletados
                    $temCodigo = !empty($slotsAtuais['imovel_proposta_codigo']);
                    $temValor = !empty($slotsAtuais['valor_proposto']);
                    $temPagamento = !empty($slotsAtuais['forma_pagamento']);
                    $temPrazo = !empty($slotsAtuais['prazo_resposta_dias']);

                    if ($temCodigo && $temValor && $temPagamento && $temPrazo) {
                        // Se urgência sinalizada, handoff imediato
                        $urgenteMsg = preg_match('/\b(urgente|urgência|hoje|imediato|rapido|rápido)\b/i', $mensagem);
                        $prazoCurto = (!empty($slotsAtuais['prazo_resposta_dias']) && (int)$slotsAtuais['prazo_resposta_dias'] <= 3);
                        if ($urgenteMsg || $prazoCurto) {
                            $proximo = 'STATE_HANDOFF';
                            if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                $thread->estado_atual = $proximo;
                                $thread->crm_status = 'proposta_enviada';
                                $thread->estado_historico = $estadoHistorico;
                                $thread->save();
                                $estadoAtual = $proximo;
                                
                                // Registrar evento: proposta enviada
                                $propertyId = null;
                                if (!empty($slotsAtuais['imovel_proposta_codigo'])) {
                                    $propertyId = $slotsAtuais['imovel_proposta_codigo'];
                                }
                                EventService::proposalSent($empresa->id, $clienteId, $propertyId, [
                                    'valor' => $slotsAtuais['valor_proposto'] ?? null,
                                    'forma_pagamento' => $slotsAtuais['forma_pagamento'] ?? null,
                                    'urgencia' => $urgenteMsg ? 'sim' : 'não',
                                ]);
                                
                                $respostaLimpa = "Sua proposta tem urgência. Vou acionar nosso corretor agora para acelerar o retorno.";
                                Log::info('[PROPOSTA] Urgência detectada, handoff imediato', [
                                    'numero_cliente' => $clienteId,
                                    'codigo' => $slotsAtuais['imovel_proposta_codigo'],
                                ]);
                            }
                        }
                        // Dados mínimos coletados - sugerir simulação se necessário
                        $formaPagamento = strtolower((string)$slotsAtuais['forma_pagamento']);

                        // Se escolheu financiamento e ainda não confirmou capacidade, oferecer simulação
                        if (strpos($formaPagamento, 'financiamento') !== false || strpos($formaPagamento, 'financiamen') !== false) {
                            if (empty($slotsAtuais['capacidade_financeira_confirmada']) || $slotsAtuais['capacidade_financeira_confirmada'] !== 'sim') {
                                // Inserir sugestão de simulação na resposta
                                if (strpos($respostaLimpa, 'simulação') === false && strpos($respostaLimpa, 'simulacao') === false) {
                                    $respostaLimpa .= "\n\n💡 *Sugestão:* Você quer que eu faça uma **simulação de financiamento** para você saber exatamente quanto vai ficar a prestação? Assim sua proposta fica mais realista e aumenta as chances de ser aceita. Pode ser?";
                                }
                                Log::info('[PROPOSTA] Sugestão de simulação inserida', [
                                    'numero_cliente' => $clienteId,
                                    'forma_pagamento' => $formaPagamento,
                                ]);
                            } else {
                                // Capacidade já foi confirmada, confirmar proposta e ir para handoff
                                $proximo = 'STATE_HANDOFF';
                                if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                    $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                    $thread->estado_atual = $proximo;
                                    $thread->estado_historico = $estadoHistorico;
                                    $thread->save();
                                    $estadoAtual = $proximo;
                                    Log::info('[PROPOSTA] Proposta completa com capacidade confirmada, indo para HANDOFF', [
                                        'numero_cliente' => $clienteId,
                                        'codigo' => $slotsAtuais['imovel_proposta_codigo'],
                                    ]);
                                }
                            }
                        } else {
                            // Pagamento não é financiamento, ir direto para handoff
                            $proximo = 'STATE_HANDOFF';
                            if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                                $thread->estado_atual = $proximo;
                                $thread->estado_historico = $estadoHistorico;
                                $thread->save();
                                $estadoAtual = $proximo;
                                Log::info('[PROPOSTA] Proposta à vista/FGTS completa, indo para HANDOFF', [
                                    'numero_cliente' => $clienteId,
                                    'codigo' => $slotsAtuais['imovel_proposta_codigo'],
                                ]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[PROPOSTA] Erro ao processar avanço do fluxo de proposta', [
                    'erro' => $e->getMessage(),
                ]);
            }

            // Regras de avanço para o fluxo de SIMULAÇÃO DE FINANCIAMENTO
            try {
                if ($estadoAtual === 'STATE_SIMULACAO') {
                    $estadoHistorico = $thread->estado_historico ?? [];

                    // Verificar se todos os dados de simulação foram coletados
                    $temValorImovel = !empty($slotsAtuais['valor_imovel_simulacao']) && is_numeric($slotsAtuais['valor_imovel_simulacao']);
                    $temEntrada = isset($slotsAtuais['entrada_disponivel_simulacao']) && is_numeric($slotsAtuais['entrada_disponivel_simulacao']);
                    $temRenda = !empty($slotsAtuais['renda_faixa_simulacao']);
                    $temPrazo = !empty($slotsAtuais['prazo_anos_simulacao']) && is_numeric($slotsAtuais['prazo_anos_simulacao']);

                    if ($temValorImovel && $temEntrada && $temRenda && $temPrazo) {
                        // Todos os dados foram coletados - executar simulação
                        try {
                            $resultadoSimulacao = SimuladorFinanciamento::simular(
                                (float)$slotsAtuais['valor_imovel_simulacao'],
                                (float)$slotsAtuais['entrada_disponivel_simulacao'],
                                (string)$slotsAtuais['renda_faixa_simulacao'],
                                (int)$slotsAtuais['prazo_anos_simulacao']
                            );

                            if ($resultadoSimulacao['sucesso']) {
                                // Formatar resultado para o usuário
                                $respostaSimulacao = SimuladorFinanciamento::formatarResultado($resultadoSimulacao);

                                // Adicionar CTA após resultado
                                $respostaSimulacao .= "\n\n🎯 *Próximos passos:*\n";
                                $respostaSimulacao .= "Quer que um especialista te ligue para simular certinho e te ajudar na proposta?\n";
                                $respostaSimulacao .= "→ Sim, me liga | → Não, obrigado";

                                $respostaLimpa = $respostaSimulacao;

                                Log::info('[SIMULACAO] Simulação calculada com sucesso', [
                                    'numero_cliente' => $clienteId,
                                    'valor_imovel' => $slotsAtuais['valor_imovel_simulacao'],
                                    'parcela_mensal' => $resultadoSimulacao['parcela']['valor_mensal'] ?? 0,
                                    'viavel' => $resultadoSimulacao['renda']['viavel'] ? 'sim' : 'não',
                                ]);
                            } else {
                                $respostaLimpa = "❌ Não consegui calcular a simulação: " . ($resultadoSimulacao['erro'] ?? 'Erro desconhecido');
                                Log::warning('[SIMULACAO] Erro ao calcular', [
                                    'numero_cliente' => $clienteId,
                                    'erro' => $resultadoSimulacao['erro'] ?? 'Desconhecido',
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('[SIMULACAO] Exceção ao executar simulador', [
                                'erro' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                            ]);
                            $respostaLimpa = "❌ Erro ao calcular a simulação. Tente novamente mais tarde.";
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[SIMULACAO] Erro ao processar avanço do fluxo de simulação', [
                    'erro' => $e->getMessage(),
                ]);
            }

            // Regras de avanço para o fluxo de SUPORTE / MANUTENÇÃO
            try {
                if ($estadoAtual === 'STATE_MANUTENCAO') {
                    $estadoHistorico = $thread->estado_historico ?? [];

                    $abertura = $this->abrirChamadoManutencao($slotsAtuais, $empresa->id, $clienteId);
                    if ($abertura) {
                        // Montar confirmação com SLA e instruções
                        $mensSeguranca = '';
                        if (!empty($abertura['seguranca'])) {
                            $mensSeguranca = "\n\n⚠️ Segurança: \n- " . implode("\n- ", $abertura['seguranca']);
                        }

                        $respostaLimpa = "✅ Chamado de manutenção aberto com sucesso (#" . $abertura['id'] . ").\nPrioridade: " . ucfirst($abertura['prioridade']) . "; prazo estimado: " . $abertura['sla'] . " horas úteis." . $mensSeguranca . "\n\nNossa equipe entrará em contato para confirmar janela de atendimento. Se preferir, posso te encaminhar para o atendimento humano agora.";

                        // Transicionar para handoff
                        $proximo = 'STATE_HANDOFF';
                        if (StateMachine::isValidTransition($estadoAtual, $proximo)) {
                            $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, $proximo);
                            $thread->estado_atual = $proximo;
                            $thread->estado_historico = $estadoHistorico;
                            $thread->save();
                            $estadoAtual = $proximo;
                            Log::info('[SUPORTE] Chamado aberto e transicionado para HANDOFF', [
                                'numero_cliente' => $clienteId,
                                'chamado_id' => $abertura['id'],
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[SUPORTE] Erro ao processar fluxo de manutenção', [
                    'erro' => $e->getMessage(),
                ]);
            }

            // Mapear escolhas numéricas e palavras-chave no estado de OBJETIVO antes do fallback
            try {
                $msgLowerTmp = strtolower(trim($mensagem));
                $onlyDigitsTmp = preg_replace('/\D/', '', $msgLowerTmp);
                if ($estadoAtual === 'STATE_OBJETIVO' && in_array($onlyDigitsTmp, ['1','2','3','4','5','6'])) {
                    $map = [
                        '1' => 'comprar_imovel',
                        '2' => 'alugar_imovel',
                        '3' => 'vender_imovel',
                        '4' => 'anunciar_para_alugar',
                        '5' => 'investimento',
                        '6' => 'falar_com_corretor',
                    ];
                    $intentAtual = $map[$onlyDigitsTmp] ?? $intentAtual;
                    $thread->intent = $intentAtual;
                    $thread->save();
                    Log::info('[OBJETIVO] Seleção numérica mapeada para intenção', [
                        'numero_cliente' => $clienteId,
                        'escolha' => $onlyDigitsTmp,
                        'intent' => $intentAtual,
                    ]);
                }

                // Se ainda sem intent clara em STATE_OBJETIVO, mapear por palavras-chave
                if ($estadoAtual === 'STATE_OBJETIVO' && $intentAtual === 'indefinido') {
                    $mapKeywords = [
                        'comprar_imovel' => '/\b(comprar|compra|comprar\s+imovel|comprar\s+imóvel|quero\s+comprar)\b/i',
                        'alugar_imovel' => '/\b(alugar|aluguel|alocar|alugar\s+imovel|alugar\s+imóvel|quero\s+alugar)\b/i',
                        'vender_imovel' => '/\b(vender|venda|anunciar\s+venda|colocar\s+a\s+venda)\b/i',
                        'anunciar_para_alugar' => '/\b(anunciar\s+para\s+alugar|anunciar\s+aluguel|por\s+para\s+alugar)\b/i',
                        'investimento' => '/\b(investimento|investir|investidor|renda\s+passiva)\b/i',
                        'falar_com_corretor' => '/\b(corretor|humano|atendente|pessoa|especialista|consultor)\b/i',
                    ];
                    foreach ($mapKeywords as $intentKey => $regex) {
                        if (preg_match($regex, $mensagem)) {
                            $intentAtual = $intentKey;
                            $thread->intent = $intentAtual;
                            $thread->save();
                            Log::info('[OBJETIVO] Palavra-chave mapeada para intenção', [
                                'numero_cliente' => $clienteId,
                                'intent' => $intentAtual,
                                'mensagem' => $mensagem,
                            ]);
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[OBJETIVO] Falha ao mapear seleção numérica', [
                    'erro' => $e->getMessage(),
                ]);
            }

            // Fallback inteligente: quando não entender, oferecer opções e alternativa; após 2 falhas, handoff
            // Evitar fallback/hand-off quando o usuário está respondendo consentimento LGPD em START/LGPD com "1" ou "2"
            $msgLowerConsent = strtolower(trim($mensagem));
            $onlyDigitsConsent = preg_replace('/\D/', '', $msgLowerConsent);
            $inStartOrLgpd = in_array($estadoAtual, ['STATE_START', 'STATE_LGPD']);
            $isConsentReply = $inStartOrLgpd && (
                preg_match('/(concordo|aceito|sim|claro|pode|autorizo|ok)/i', $msgLowerConsent) ||
                preg_match('/(nao|não|prefiro|sem cadastro|recuso|neg|n\s*ao)/i', $msgLowerConsent) ||
                in_array($onlyDigitsConsent, ['1','2'])
            );

            if ($intentAtual === 'indefinido' && !$isConsentReply) {
                $tentativas = ($thread->fallback_tentativas ?? 0) + 1;
                $thread->fallback_tentativas = $tentativas;
                $thread->save();

                if ($tentativas >= 2) {
                    // Handoff para humano
                    $estadoHistorico = $thread->estado_historico ?? [];
                    $estadoAnterior = $estadoAtual;
                    $respostaLimpa = "Vou te conectar a um corretor humano para te ajudar melhor agora. 👍";
                    $thread->etapa_fluxo = 'handoff';
                    $thread->estado_atual = 'STATE_HANDOFF';
                    $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAnterior, 'STATE_HANDOFF');
                    $thread->estado_historico = $estadoHistorico;
                    $thread->save();
                    $estadoAtual = 'STATE_HANDOFF';
                } else {
                    // Sugestões simples e alternativa estruturada
                    $respostaLimpa = "Não entendi certinho. Você quer comprar, alugar ou falar com um corretor?\n\nSe preferir, podemos tentar de outro jeito:\nMe diga bairro + valor máximo + quartos.\nEx: “Tatuapé até 450 mil 2 quartos”";
                }
            }

            // Handoff por indecisão: após 2 ciclos de refinamento e continuar filtrando/indefinido
            if (($thread->refino_ciclos ?? 0) >= 2 && in_array($estadoAtual, ['STATE_REFINAR','STATE_MATCH_RESULT'])) {
                if (in_array($intentAtual, ['filtrar','indefinido'])) {
                    $estadoHistorico = $thread->estado_historico ?? [];
                    $estadoAnterior = $estadoAtual;
                    $respostaLimpa = "Percebi que seguimos refinando bastante. Vou te conectar a um corretor humano para te ajudar a decidir com rapidez. 😊";
                    $thread->etapa_fluxo = 'handoff';
                    $thread->estado_atual = 'STATE_HANDOFF';
                    $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAnterior, 'STATE_HANDOFF');
                    $thread->estado_historico = $estadoHistorico;
                    $thread->save();
                    $estadoAtual = 'STATE_HANDOFF';
                }
            }

            // Fluxo simplificado: menu principal direto
            // Interpreta escolhas numéricas do menu
            if ($etapaFluxo === 'boas_vindas') {
                $msgLower = strtolower(trim($mensagem));
                $onlyDigits = preg_replace('/\D/', '', $msgLower);

                $menuMap = [
                    '1' => 'comprar_imovel',
                    '2' => 'alugar_imovel',
                    '3' => 'documentos',
                    '4' => 'opcoes_pagamento',
                    '5' => 'pagamentos',
                    '6' => 'nota_fiscal',
                    '7' => 'falar_com_corretor',
                    '8' => 'encerrar',
                ];

                if (isset($menuMap[$onlyDigits])) {
                    $escolha = $menuMap[$onlyDigits];
                    Log::info('[MENU] Opção escolhida', [
                        'numero_cliente' => $clienteId,
                        'opcao' => $onlyDigits,
                        'descricao' => $escolha,
                    ]);

                    // Rotear para a opção escolhida
                    if ($escolha === 'comprar_imovel') {
                        $thread->etapa_fluxo = 'qualificacao';
                        $thread->objetivo = 'comprar';
                        $respostaLimpa = "Perfeito! Vamos buscar o imóvel ideal para você.\n\nQual tipo de imóvel você procura? (apartamento, casa, kitnet, etc)";
                    } elseif ($escolha === 'alugar_imovel') {
                        $thread->etapa_fluxo = 'qualificacao';
                        $thread->objetivo = 'alugar';
                        $respostaLimpa = "Ótimo! Vou te ajudar a encontrar um bom imóvel para aluguel.\n\nQual tipo de imóvel você procura? (apartamento, casa, kitnet, etc)";
                    } elseif ($escolha === 'documentos') {
                        $respostaLimpa = "📄 *DOCUMENTOS NECESSÁRIOS*\n\n✅ *Para comprar:*\n- RG e CPF\n- Comprovante de renda\n- Extrato bancário\n- Aprovação em crédito (se financiamento)\n\n✅ *Para alugar:*\n- RG e CPF\n- Comprovante de renda\n- Referências pessoais\n- Antecedentes (se solicitado)\n\nPrecisa de mais informações? Digite uma opção: 1️⃣ Comprar | 2️⃣ Alugar | 3️⃣ Outro";
                    } elseif ($escolha === 'opcoes_pagamento') {
                        $respostaLimpa = "💳 *OPÇÕES DE PAGAMENTO*\n\n💰 *À vista:* Desconto imediato\n🏦 *Financiamento:* Até 360 meses\n🏛️ *FGTS:* Se elegível\n📊 *Parcelado:* Condições especiais\n\nQuer simular um financiamento? Digite 1️⃣ Sim | 2️⃣ Não | 3️⃣ Voltar ao menu";
                    } elseif ($escolha === 'pagamentos') {
                        $respostaLimpa = "💸 *GERENCIAR PAGAMENTOS*\n\n🔍 Consultar:\n- Status do pagamento\n- Histórico de transações\n- Extrato de faturas\n- Boletos em aberto\n\n📞 Precisa de ajuda? Digite uma opção:\n1️⃣ Consultar pagamento | 2️⃣ Pedir recibo | 3️⃣ Voltar ao menu";
                    } elseif ($escolha === 'nota_fiscal') {
                        $respostaLimpa = "📋 *NOTA FISCAL*\n\nA nota fiscal será emitida automaticamente após a conclusão da transação.\n\n📄 Informações necessárias:\n- Dados pessoais\n- CPF ou CNPJ\n- Dados bancários (para transferência)\n\nDeseja voltar ao menu? 1️⃣ Sim | 2️⃣ Falar com corretor";
                    } elseif ($escolha === 'falar_com_corretor') {
                        $thread->etapa_fluxo = 'handoff';
                        $respostaLimpa = "👨‍💼 Vou te conectar a um corretor agora.\n\nPor favor, aguarde um momento...\n\n---\n\nMeu nome é Lucas e darei continuidade ao seu atendimento. Como posso ajudá-lo?";
                    } elseif ($escolha === 'encerrar') {
                        $respostaLimpa = "👋 Obrigado por usar nosso serviço!\n\nFicamos felizes em poder ajudar. Até logo! 😊\n\nSe precisar de ajuda novamente, é só chamar. Volte sempre!";
                    }

                    $thread->save();
                }
            } elseif ($estadoAtual === 'STATE_HANDOFF') {
                // Recuperar para o menu se o usuário voltar
            } elseif ($intentAtual === 'comprar_imovel') {
                $thread->objetivo = 'comprar';
                $thread->etapa_fluxo = 'qualificacao';
                $thread->save();
                Log::info('[INTENT-COMPRA] Fluxo iniciado', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'alugar_imovel') {
                $thread->objetivo = 'alugar';
                $thread->etapa_fluxo = 'qualificacao';
                $thread->save();
                Log::info('[INTENT-ALUGUEL] Fluxo iniciado', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'vender_imovel') {
                $thread->objetivo = 'vender';
                $thread->etapa_fluxo = 'captacao';
                $thread->save();
                Log::info('[INTENT-VENDA] Fluxo iniciado', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'anunciar_para_alugar') {
                $thread->objetivo = 'anunciar_aluguel';
                $thread->etapa_fluxo = 'captacao';
                $thread->save();
                Log::info('[INTENT-CAPTACAO-ALUGUEL] Fluxo iniciado', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'investimento') {
                $thread->objetivo = 'investir';
                $thread->etapa_fluxo = 'qualificacao';
                $thread->save();
                Log::info('[INTENT-INVESTIMENTO] Fluxo iniciado', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'ver_imoveis') {
                $thread->etapa_fluxo = 'catalogo';
                $thread->save();
                Log::info('[INTENT-VER-IMOVEIS] Movendo para catálogo', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'agendar_visita') {
                $thread->etapa_fluxo = 'agendamento';
                $thread->crm_status = 'em_visita';
                $thread->save();
                Log::info('[INTENT-AGENDAR] Movendo para agendamento', ['numero_cliente' => $clienteId]);
                
                // Registrar evento: visita agendada (quando confirmada será feito no fluxo de agendamento)
            } elseif ($intentAtual === 'fazer_proposta') {
                $thread->etapa_fluxo = 'proposta';
                $thread->save();
                Log::info('[INTENT-PROPOSTA] Movendo para proposta', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'simulacao_financiamento') {
                $thread->etapa_fluxo = 'proposta';
                $thread->save();
                Log::info('[INTENT-SIMULACAO] Movendo para proposta/simulação', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'documentos') {
                $thread->etapa_fluxo = 'proposta';
                $thread->save();
                Log::info('[INTENT-DOCUMENTOS] Movendo para documentos', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'status_atendimento') {
                $thread->etapa_fluxo = 'suporte';
                $thread->objetivo = 'suporte';
                $thread->save();
                // Garantir slots de suporte presentes
                $slotsAtuais = is_array($thread->slots) ? $thread->slots : (json_decode((string)$thread->slots, true) ?: []);
                foreach (\App\Services\SlotsSchema::SLOTS_SUPORTE as $k => $v) {
                    if (!array_key_exists($k, $slotsAtuais)) {
                        $slotsAtuais[$k] = null;
                    }
                }
                $thread->slots = $slotsAtuais;
                $thread->save();
                Log::info('[INTENT-STATUS] Movendo para suporte', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'falar_com_corretor') {
                $thread->etapa_fluxo = 'handoff';
                $thread->save();
                Log::info('[INTENT-HANDOFF] Solicitando handoff imediato', ['numero_cliente' => $clienteId]);
                // Forçar transição de estado para HANDOFF
                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, 'STATE_HANDOFF');
                $thread->estado_atual = 'STATE_HANDOFF';
                $thread->estado_historico = $estadoHistorico;
                $thread->save();
                $estadoAtual = 'STATE_HANDOFF';
                $respostaLimpa = "👨‍💼 Vou te conectar a um corretor agora.\n\nPor favor, aguarde um momento...\n\n---\n\nMeu nome é Lucas e darei continuidade ao seu atendimento. Como posso ajudá-lo?";
            } elseif ($intentAtual === 'reclamacao_manutencao') {
                $thread->etapa_fluxo = 'suporte';
                $thread->objetivo = 'suporte';
                $thread->save();
                // Garantir slots de suporte presentes
                $slotsAtuais = is_array($thread->slots) ? $thread->slots : (json_decode((string)$thread->slots, true) ?: []);
                foreach (\App\Services\SlotsSchema::SLOTS_SUPORTE as $k => $v) {
                    if (!array_key_exists($k, $slotsAtuais)) {
                        $slotsAtuais[$k] = null;
                    }
                }
                $thread->slots = $slotsAtuais;
                $thread->save();
                Log::info('[INTENT-RECLAMACAO] Movendo para suporte', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'documentacao_complexa') {
                $thread->etapa_fluxo = 'handoff';
                $thread->save();
                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, 'STATE_HANDOFF');
                $thread->estado_atual = 'STATE_HANDOFF';
                $thread->estado_historico = $estadoHistorico;
                $thread->save();
                $estadoAtual = 'STATE_HANDOFF';
                $respostaLimpa = "Este caso de documentação é complexo (inventário/penhora/usucapião). Vou te conectar a um especialista para analisar e orientar corretamente.";
                Log::info('[HANDOFF] Documentação complexa, handoff', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'ameaca_juridica') {
                $thread->etapa_fluxo = 'handoff';
                $thread->save();
                $estadoHistorico = StateMachine::registerTransition($estadoHistorico, $estadoAtual, 'STATE_HANDOFF');
                $thread->estado_atual = 'STATE_HANDOFF';
                $thread->estado_historico = $estadoHistorico;
                $thread->save();
                $estadoAtual = 'STATE_HANDOFF';
                $respostaLimpa = "Entendo a gravidade. Vou transferir para nosso atendimento especializado imediatamente para resolver da melhor forma.";
                Log::info('[HANDOFF] Queixa/ameaça jurídica, handoff', ['numero_cliente' => $clienteId]);
            } elseif ($intentAtual === 'negativa_sair') {
                Log::info('[INTENT-SAIR] Usuário saindo', ['numero_cliente' => $clienteId]);
            } elseif (preg_match('/(agendar|visita|horário|data)/i', $mensagem) && $etapaFluxo === 'catalogo') {
                $thread->etapa_fluxo = 'agendamento';
                $thread->save();
                Log::info('[CATALOGO] Agendar visita selecionado', ['numero_cliente' => $clienteId]);
            } elseif (preg_match('/(confirmado|ok|pronto|sim)/i', $mensagem) && $etapaFluxo === 'agendamento') {
                $thread->etapa_fluxo = 'pos_atendimento';
                $thread->save();
                Log::info('[AGENDAMENTO] Visita confirmada', ['numero_cliente' => $clienteId]);
            }

            if ($respostaLimpa === '') {
                $respostaLimpa = 'Tudo certo, obrigado pelas informações!';
            }

            // Processar match result quando estado é STATE_MATCH_RESULT
            if ($estadoAtual === 'STATE_MATCH_RESULT') {
                $resultadoMatch = $this->processMatchResult($slotsAtuais, $objetivo);
                if ($resultadoMatch && !empty($resultadoMatch['imoveis_exatos'] || $resultadoMatch['imoveis_quase_la'])) {
                    $respostaLimpa = $resultadoMatch['mensagem'];
                    
                    // Registrar visualizações de propriedades
                    foreach (array_merge($resultadoMatch['imoveis_exatos'] ?? [], $resultadoMatch['imoveis_quase_la'] ?? []) as $imovel) {
                        if (!empty($imovel['id'])) {
                            EventService::propertyViewed($empresa->id, $clienteId, $imovel['id']);
                        }
                    }
                    
                    Log::info('[MATCH-RESULT] Recomendações geradas', [
                        'numero_cliente' => $clienteId,
                        'exatos' => count($resultadoMatch['imoveis_exatos'] ?? []),
                        'quase_la' => count($resultadoMatch['imoveis_quase_la'] ?? []),
                    ]);
                }
            }

            // Remover tags de slots (apenas para log interno, não enviadas ao usuário)
            $respostaBrutaLimpa = preg_replace('/\[\[SLOTS\]\].*?\[\[\/SLOTS\]\]/s', '', $respostaBruta ?? '');

            $atalhosPadrao = '';
            $respostaParaEnvio = trim($respostaLimpa);

            Log::info('Resposta final da IA (job):', [
                'resposta_limpa' => $respostaLimpa,
                'resposta_bruta' => trim($respostaBrutaLimpa),
                'resposta_envio' => $respostaParaEnvio,
                'slots_salvos' => $slotsExtraidos,
                'estado_atual' => $estadoAtual,
            ]);

        } catch (\Throwable $e) {
            Log::error('[DEBUG] Erro capturado', [
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'tipo' => get_class($e),
            ]);
            return;
        }

        // Evolution: alguns endpoints aceitam number (apenas dígitos) e outros jid completo. Enviamos ambos.
        // Preferir senderPn (JID real do contato) quando fornecido pelo webhook
        $jidEnvio = $senderPn ?: $remetente;

        // Normaliza JID para envios: Evolution espera jid com @s.whatsapp.net para contatos individuais
        if ($jidEnvio && str_ends_with($jidEnvio, '@lid')) {
            $jidEnvio = preg_replace('/@lid$/', '@s.whatsapp.net', $jidEnvio);
        }

        // Extrai número (apenas dígitos) e normaliza para E.164 (inclui código do país)
        $numeroExtraido = $isGrupo
            ? $jidEnvio
            : preg_replace('/\D/', '', preg_replace('/@.+$/', '', ($jidEnvio ?? '')));

        $numeroEnvio = $isGrupo ? $jidEnvio : $this->normalizeToE164($numeroExtraido);

        // Recompõe JID com E.164 quando não for grupo
        if (!$isGrupo && $numeroEnvio) {
            $jidEnvio = $numeroEnvio . '@s.whatsapp.net';
        }

        // Verificação prévia (opcional): existência do número no WhatsApp
        if (config('app.check_number_before_send') && !$isGrupo) {
            $checkUrl = config('services.evolution.url') . "/chat/whatsappNumbers/{$instance}";
            $checkResponse = Http::withHeaders([
                'apikey' => config('services.evolution.key'),
            ])->post($checkUrl, [
                'numbers' => [$numeroEnvio],
            ]);

            if ($checkResponse->successful()) {
                $checkData = $checkResponse->json();
                $exists = false;

                if (is_array($checkData) && count($checkData) > 0) {
                    $exists = $checkData[0]['exists'] ?? false;
                }

                if (!$exists) {
                    Log::warning('Número pode não existir no WhatsApp; prosseguindo mesmo assim.', [
                        'number' => $numeroEnvio,
                        'checkResponse' => $checkData,
                    ]);
                }
            } else {
                Log::warning('Falha na verificação de existência do número. Prosseguindo com envio.', [
                    'number' => $numeroEnvio,
                    'status' => $checkResponse->status(),
                ]);
            }
        } elseif (!$isGrupo) {
            Log::debug('[DEBUG] Verificação de número desabilitada; enviando mesmo assim.', [
                'number' => $numeroEnvio,
            ]);
        }

        // Fixar destino em cima do identificador normalizado do cliente para evitar envios incorretos
        $numeroEnvio = $isGrupo ? $numeroEnvio : $this->normalizeToE164($clienteId);
        if (!$isGrupo) {
            $jidEnvio = $numeroEnvio ? ($numeroEnvio . '@s.whatsapp.net') : $jidEnvio;
        }

        // Guard rail final: só envia se houver número válido
        if (!$numeroEnvio) {
            Log::warning('[BLOQUEADO] Sem número válido para envio', [
                'cliente' => $clienteId,
                'jid' => $jidEnvio,
                'instance' => $instance,
            ]);
            return;
        }

        // Evolution API: sempre precisa de 'number', seja grupo ou individual
        $apiUrl = config('services.evolution.url') . "/message/sendText/{$instance}";

        $payload = [
            'number' => $isGrupo ? $jidEnvio : $numeroEnvio,
            'text' => $respostaParaEnvio,
        ];

        // Incluir JID no payload para maximizar compatibilidade (configurável)
        if ($isGrupo || config('app.always_include_jid')) {
            $payload['jid'] = $jidEnvio;
        }

        // Tentativas com backoff exponencial para mitigar erros intermitentes (ex.: Connection Closed/500)
        $sendAttempts = 0;
        $maxAttempts = 3;
        do {
            $sendAttempts++;
            Log::debug('Tentativa de envio de mensagem via Evolution', [
                'tentativa' => $sendAttempts,
                'payload' => $payload,
            ]);
            $sendResponse = Http::withHeaders([
                'apikey' => config('services.evolution.key'),
            ])->post($apiUrl, $payload);

            if ($sendResponse->successful()) {
                break;
            }

            if ($sendResponse->status() >= 500) {
                $sleep = $sendAttempts === 1 ? 1 : ($sendAttempts === 2 ? 2 : 4);
                Log::warning('Erro 5xx na Evolution API; aplicando backoff', [
                    'status' => $sendResponse->status(),
                    'body' => $sendResponse->body(),
                    'aguardando_segundos' => $sleep,
                ]);
                sleep($sleep);
            } else {
                // Para 4xx não adianta repetir o mesmo payload
                break;
            }
        } while ($sendAttempts < $maxAttempts);

        // Fallback para grupos: se falhar com 4xx ou esgotar tentativas, tentar com 'jid' apenas.
        if ($isGrupo && !$sendResponse->successful()) {
            Log::warning('Envio para grupo falhou; tentando fallback com jid.', [
                'status' => $sendResponse->status(),
                'body' => $sendResponse->body(),
                'jid' => $jidEnvio,
            ]);

            $fallbackPayload = [
                'jid' => $jidEnvio,
                'text' => $respostaParaEnvio,
            ];

            $sendAttempts = 0;
            do {
                $sendAttempts++;
                $sendResponse = Http::withHeaders([
                    'apikey' => config('services.evolution.key'),
                ])->post($apiUrl, $fallbackPayload);
                if ($sendResponse->successful()) {
                    break;
                }
                if ($sendResponse->status() >= 500) {
                    $sleep = $sendAttempts === 1 ? 1 : 2;
                    sleep($sleep);
                } else {
                    break;
                }
            } while ($sendAttempts < 2);
        }

        // Fallback para contatos individuais: se falhar após tentativas com 'number', tentar envio com 'jid' apenas.
        if (!$isGrupo && !$sendResponse->successful()) {
            Log::warning('Envio para contato individual falhou; tentando fallback com jid.', [
                'status' => $sendResponse->status(),
                'body' => $sendResponse->body(),
                'jid' => $jidEnvio,
            ]);

            // Algumas instalações da Evolution exigem sempre a propriedade "number" mesmo com JID
            $fallbackPayload = [
                'jid' => $jidEnvio,
                'number' => $numeroEnvio,
                'text' => $respostaParaEnvio,
            ];

            $sendAttempts = 0;
            do {
                $sendAttempts++;
                $sendResponse = Http::withHeaders([
                    'apikey' => config('services.evolution.key'),
                ])->post($apiUrl, $fallbackPayload);
                if ($sendResponse->successful()) {
                    break;
                }
                if ($sendResponse->status() >= 500) {
                    $sleep = $sendAttempts === 1 ? 1 : 2;
                    sleep($sleep);
                } else {
                    break;
                }
            } while ($sendAttempts < 2);
        }

        Log::info('Resposta da API Evolution ao envio (job):', [
            'status' => $sendResponse->status(),
            'body' => $sendResponse->body(),
            'number' => $numeroEnvio,
            'jid' => $jidEnvio,
            'source' => 'agente-ia',
        ]);
    }

    /**
     * Normaliza número para formato E.164 (adiciona código do país quando ausente)
     */
    private function normalizeToE164(?string $numero): ?string
    {
        if (!$numero) { return $numero; }
        $digits = preg_replace('/\D/', '', $numero);
        $country = (string) (config('app.whatsapp_country_code') ?? '55');
        if ($digits === '') { return $digits; }

        if (!str_starts_with($digits, $country)) {
            $digits = $country . $digits;
        }
        return $digits;
    }

    /**
     * Auxiliar: abrir chamado de manutenção
     */
    private function abrirChamadoManutencao(array $slotsAtuais, $empresaId, string $numeroCliente): ?array
    {
        $endereco = $slotsAtuais['suporte_endereco_unidade'] ?? null;
        $tipo = $slotsAtuais['suporte_tipo_problema'] ?? null;
        $urgencia = strtolower((string)($slotsAtuais['suporte_urgencia'] ?? ''));
        $midia = $slotsAtuais['suporte_midia_link'] ?? null;

        if (!$endereco || !$tipo || !$urgencia) {
            return null; // ainda faltam dados
        }

        // SLA e prioridade
        $slaHoras = 48;
        $prioridade = 'normal';
        if ($urgencia === 'alta') { $slaHoras = 24; $prioridade = 'alta'; }
        elseif ($urgencia === 'media' || $urgencia === 'média') { $slaHoras = 48; $prioridade = 'normal'; }
        elseif ($urgencia === 'baixa') { $slaHoras = 72; $prioridade = 'baixa'; }

        // Mensagens de segurança básicas
        $seguranca = [];
        $tipoLower = strtolower($tipo);
        if (preg_match('/(vazamento|hidráulic|hidraulic|agua|água)/i', $tipoLower)) {
            $seguranca[] = 'Se possível, feche o registro de água da unidade até o atendimento.';
        }
        if (preg_match('/(elétrica|eletric|choque|tomada|chuveiro)/i', $tipoLower)) {
            $seguranca[] = 'Se houver risco, desligue o disjuntor da área afetada e evite usar o equipamento.';
        }
        if (preg_match('/(gas|gás)/i', $tipoLower)) {
            $seguranca[] = 'Feche o registro de gás, mantenha o ambiente ventilado e evite acionamentos elétricos.';
        }

        $chamado = SuporteChamado::create([
            'empresa_id' => $empresaId,
            'numero_cliente' => $numeroCliente,
            'nome_cliente' => $slotsAtuais['nome'] ?? null,
            'telefone_whatsapp' => $slotsAtuais['telefone_whatsapp'] ?? null,
            'unidade_endereco' => $endereco,
            'tipo_problema' => $tipo,
            'urgencia' => $urgencia,
            'midia_link' => $midia,
            'status' => 'aberto',
            'prioridade' => $prioridade,
            'sla_estimativa_horas' => $slaHoras,
            'observacoes' => null,
        ]);

        return [
            'id' => $chamado->id,
            'sla' => $slaHoras,
            'prioridade' => $prioridade,
            'seguranca' => $seguranca,
        ];
    }

    /**
     * Gerar recomendações de imóveis com match scoring
     * Chamado quando estado é STATE_MATCH_RESULT
     */
    private function processMatchResult(array $slots, string $objetivo): ?array
    {
        // Simulação de catálogo de imóveis (substituir por query de DB real)
        $imoveis = $this->getPropertyCatalog($objetivo);

        if (empty($imoveis)) {
            return [
                'mensagem' => "Desculpe, não encontrei imóveis no catálogo que correspondam ao seu perfil no momento. Posso:\n1. Falar com um corretor para opções customizadas\n2. Voltar e ajustar os filtros",
                'imoveis_exatos' => [],
                'imoveis_quase_la' => [],
            ];
        }

        // Gerar recomendações usando MatchingEngine
        return MatchingEngine::generateRecommendations($imoveis, $slots, maxResultados: 8);
    }

    /**
     * Obter catálogo de imóveis do banco de dados ou API externa
     * TODO: Conectar com AgenteGerado model ou API de imóveis
     */
    private function getPropertyCatalog(string $objetivo): array
    {
        // Exemplo de dados fictícios - substituir por query real de imóveis
        return [
            [
                'id' => 1,
                'titulo' => 'Apt. 2 quartos em Perdizes',
                'bairro' => 'Perdizes',
                'valor' => 450000,
                'quartos' => 2,
                'vagas' => 1,
                'tags' => ['pet_friendly', 'varanda'],
            ],
            [
                'id' => 2,
                'titulo' => 'Apt. 3 quartos em Vila Mariana',
                'bairro' => 'Vila Mariana',
                'valor' => 580000,
                'quartos' => 3,
                'vagas' => 2,
                'tags' => ['suíte', 'varanda'],
            ],
            [
                'id' => 3,
                'titulo' => 'Apt. 2 quartos em Pinheiros',
                'bairro' => 'Pinheiros',
                'valor' => 520000,
                'quartos' => 2,
                'vagas' => 1,
                'tags' => ['pet_friendly'],
            ],
            [
                'id' => 4,
                'titulo' => 'Apt. 4 quartos em Imirim',
                'bairro' => 'Imirim',
                'valor' => 420000,
                'quartos' => 4,
                'vagas' => 1,
                'tags' => ['suíte', 'quintal'],
            ],
            [
                'id' => 5,
                'titulo' => 'Apt. 2 quartos em Morumbi',
                'bairro' => 'Morumbi',
                'valor' => 650000,
                'quartos' => 2,
                'vagas' => 2,
                'tags' => ['suíte', 'piscina', 'pet_friendly'],
            ],
        ];
    }

    /**
     * Processa arquivos de mídia (imagens, PDFs, áudio, vídeo)
     * Usa MediaProcessor para análise inteligente e integra resultado no fluxo conversacional
     */
    private function processarMedia(string $tipoMensagem, array $msgData, string $instance, string $remetente, Thread $thread, string $clienteId)
    {
        try {
            $mediaProcessor = new MediaProcessor();
            
            // Processa o arquivo baseado no tipo
            if ($tipoMensagem === 'video') {
                // Vídeo não é suportado por hora
                $resposta = '🎥 Recebemos seu vídeo! Ainda estou aprendendo a processar vídeos. Pode descrever o conteúdo em texto ou enviar como imagem/PDF? Sua paciência é valorizada! 😊';
                
                Log::info('Vídeo recebido; resposta enviada', [
                    'cliente' => $clienteId,
                    'thread_id' => $thread->id
                ]);
            } else {
                // Processa imagem, documento ou áudio
                $resultado = $mediaProcessor->processar($msgData);
                
                if ($resultado['success'] === false) {
                    $resposta = "❌ Desculpe, não consegui processar o arquivo: " . ($resultado['erro'] ?? 'Erro desconhecido');
                    Log::warning('Erro ao processar mídia', [
                        'tipo' => $tipoMensagem,
                        'cliente' => $clienteId,
                        'erro' => $resultado['erro'] ?? 'Unknown'
                    ]);
                } else {
                    // Sucesso! Integra conteúdo extraído no contexto da conversa
                    $conteudo = $resultado['conteudo_extraido'] ?? '';
                    $tipoMidia = $resultado['tipo_midia'] ?? $tipoMensagem;
                    
                    // Monta resposta contextualizada
                    $resposta = $this->montarRespostaMedia($tipoMidia, $conteudo, $thread);
                    
                    // Armazena informação da mídia no histórico do thread
                    if ($thread->estado_historico === null) {
                        $thread->estado_historico = [];
                    }
                    
                    $historico = is_array($thread->estado_historico) ? $thread->estado_historico : [];
                    $historico[] = [
                        'timestamp' => now()->toIso8601String(),
                        'tipo' => 'midia_processada',
                        'tipo_midia' => $tipoMidia,
                        'arquivo_local' => $resultado['arquivo_local'] ?? null,
                        'conteudo_chars' => strlen($conteudo),
                        'metadados' => $resultado['metadados'] ?? []
                    ];
                    
                    $thread->update(['estado_historico' => $historico]);
                    
                    Log::info('Mídia processada com sucesso', [
                        'tipo' => $tipoMidia,
                        'cliente' => $clienteId,
                        'thread_id' => $thread->id,
                        'arquivo' => $resultado['arquivo_local'] ?? null
                    ]);
                }
            }
            
            // Envia resposta via Evolution/WhatsApp
            $response = Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/message/sendText/{$instance}", [
                    'number' => $remetente,
                    'text' => $resposta,
                ]);

            if ($response->failed()) {
                Log::error('Falha ao enviar resposta de mídia via Evolution', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'cliente' => $clienteId
                ]);
            }

        } catch (Exception $e) {
            Log::error('Erro ao processar mídia no job', [
                'tipo' => $tipoMensagem,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Envia resposta de erro genérica
            Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/message/sendText/{$instance}", [
                    'number' => $remetente,
                    'text' => '⚠️ Desculpe, ocorreu um erro ao processar seu arquivo. Por favor, tente novamente mais tarde.',
                ]);
        }
    }

    /**
     * Monta resposta contextualizada baseada no tipo de mídia e seu conteúdo
     * Integra o conteúdo extraído no fluxo conversacional atual
     */
    private function montarRespostaMedia(string $tipoMidia, string $conteudo, Thread $thread): string
    {
        $estadoAtual = $thread->estado_atual ?? 'STATE_START';
        
        switch ($tipoMidia) {
            case 'image':
                return "✅ *Imagem analisada com sucesso!*\n\n" .
                       "Aqui está o que identifiquei:\n\n" .
                       $conteudo . "\n\n" .
                       "Como posso ajudá-lo com relação a isso? 🤔";
            
            case 'pdf':
                $preview = substr($conteudo, 0, 300);
                return "✅ *PDF processado com sucesso!*\n\n" .
                       "**Conteúdo extraído:**\n\n" .
                       $preview .
                       (strlen($conteudo) > 300 ? "\n\n...(conteúdo truncado)" : "") .
                       "\n\nPodem me contar mais sobre o que você gostaria de fazer com este documento? 📄";
            
            case 'document':
                $preview = substr($conteudo, 0, 300);
                return "✅ *Documento processado!*\n\n" .
                       "**Conteúdo identificado:**\n\n" .
                       $preview .
                       (strlen($conteudo) > 300 ? "\n\n...(conteúdo continua)" : "") .
                       "\n\nComo posso ajudar com este documento? 📑";
            
            case 'audio':
                return "✅ *Arquivo de áudio recebido!*\n\n" .
                       $conteudo . "\n\n" .
                       "Você pode me enviar o conteúdo em texto ou descrição? 🎙️";
            
            default:
                return "✅ *Arquivo recebido e analisado!*\n\n" .
                       $conteudo . "\n\n" .
                       "Como posso ajudá-lo? 😊";
        }
    }
}

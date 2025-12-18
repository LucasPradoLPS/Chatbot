<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\InstanciaWhatsapp;
use App\Models\Agente;
use App\Models\AgenteGerado;
use App\Models\Empresa;
use App\Models\MensagensMemoria;
use App\Models\IaIntervencao;
use App\Models\Thread;

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
        // If flag is missing, assume message is from user (not from the bot).
        $fromMe = $data['data']['key']['fromMe'] ?? false;
        $isGrupo = $remetente && str_ends_with($remetente, '@g.us');
        $source = $data['data']['source'] ?? null;
        $msgData = $data['data']['message'] ?? [];

        Log::info('ProcessWhatsappMessage: start', [
            'instance' => $instance,
            'remetente' => $remetente,
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

        $allowSelfChat = (bool) config('app.allow_self_chat');

        // Intervenção humana: mensagens iniciadas manualmente pelo usuário (web/ios)
        if (!$allowSelfChat && $fromMe && in_array($source, ['ios', 'web'])) {
            IaIntervencao::updateOrCreate(
                [
                    'empresa_id' => $instancia->empresa_id,
                    'numero_cliente' => $remetente,
                ],
                [
                    'intervencao_em' => now(),
                ]
            );

            Log::info("Intervenção humana registrada pelo job para o cliente {$remetente}.");
            return;
        }

        // Evitar loop: mesmo com ALLOW_SELF_CHAT=true, ignore mensagens que vieram da própria IA (source "unknown").
        if ($fromMe && ($source === 'unknown')) {
            Log::info('Mensagem enviada pela IA. Ignorando para evitar loop. (job)');
            return;
        }

        if (!$allowSelfChat && $fromMe) {
            Log::info('Mensagem enviada pela IA. Ignorando para evitar loop. (job)');
            return;
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

        if ($tipoMensagem === 'video') {
            Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/message/sendText/{$instance}", [
                    'number' => $remetente,
                    'text' => 'Recebemos seu vídeo, mas ainda não conseguimos processar vídeos. 😊 Pode enviar por áudio ou texto?',
                ]);
            Log::info('Vídeo recebido; resposta padrão enviada.');
            return;
        }

        $empresa = Empresa::find($instancia->empresa_id);
        if (!$empresa) {
            Log::warning('[ERRO] Empresa não encontrada', ['empresa_id' => $instancia->empresa_id]);
            Log::warning('[BLOQUEADO] Empresa ID ' . $instancia->empresa_id . ' não existe para: ' . $remetente);
            return;
        }

        $agente = Agente::where('empresa_id', $empresa->id)->first();

        if (!$agente || !$agente->ia_ativa) {
            Log::info('[BLOQUEADO] IA desativada para a empresa ' . $empresa->id . ' | Agente: ' . ($agente ? 'EXISTE' : 'NÃO EXISTE') . ' de: ' . $remetente);
            return;
        }

        if ($isGrupo && !$agente->responder_grupo) {
            Log::info('[BLOQUEADO] Mensagem de grupo ignorada para empresa ' . $empresa->id . ' de: ' . $remetente);
            return;
        }

        $intervencao = IaIntervencao::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $remetente)
            ->where('intervencao_em', '>=', now()->subMinutes(60))
            ->first();

        if ($intervencao) {
            Log::info('[BLOQUEADO] IA pausada por intervenção humana para: ' . $remetente);
            return;
        }

        $limite = $empresa->memoria_limite ?? 4;

        MensagensMemoria::create([
            'empresa_id' => $empresa->id,
            'numero_cliente' => $remetente,
            'mensagem' => $mensagem ?? '[imagem recebida]',
            'tipo' => $tipoMensagem,
        ]);

        // Manter APENAS as últimas $limite mensagens na memória.
        MensagensMemoria::where('empresa_id', $empresa->id)
            ->where('numero_cliente', $remetente)
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

            $thread = Thread::where('empresa_id', $empresa->id)
                ->where('numero_cliente', $remetente)
                ->where('updated_at', '>=', now()->subHours(12))
                ->first();

            if (!$thread) {
                $threadResponse = Http::withToken(config('services.openai.key'))
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->post('https://api.openai.com/v1/threads', []);

                $threadId = $threadResponse['id'] ?? null;

                // Se já existir um registro antigo (fora da janela de 12h), atualiza em vez de tentar criar um novo
                $thread = Thread::updateOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'numero_cliente' => $remetente,
                    ],
                    [
                        'thread_id' => $threadId,
                    ]
                );
            } else {
                $threadId = $thread->thread_id;
                $thread->touch();
            }

            $conteudo = [];
            $memorias = MensagensMemoria::where('empresa_id', $empresa->id)
                ->where('numero_cliente', $remetente)
                ->orderBy('created_at')
                ->get();

            foreach ($memorias as $m) {
                $conteudo[] = ['type' => 'text', 'text' => $m->mensagem];
            }

            Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                    'role' => 'user',
                    'content' => $conteudo,
                ]);

            $runResponse = Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
                    'assistant_id' => $assistantId,
                ]);

            $runId = $runResponse['id'] ?? null;

            do {
                sleep(1);
                $status = Http::withToken(config('services.openai.key'))
                    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                    ->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
            } while (($status['status'] ?? null) !== 'completed');

            $messages = Http::withToken(config('services.openai.key'))
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->get("https://api.openai.com/v1/threads/{$threadId}/messages");

            $respostaFinal = $messages['data'][0]['content'][0]['text']['value'] ?? 'Desculpe, não consegui responder.';

            Log::info('Resposta final da IA (job):', ['resposta' => $respostaFinal]);

            $palavras = str_word_count(strip_tags($respostaFinal));
            $tempoPorPalavra = rand(200, 400);
            $delayMs = min($palavras * $tempoPorPalavra, 8000);
            usleep($delayMs * 1000);

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
        $jidEnvio = $remetente;
        $numeroEnvio = $isGrupo
            ? $jidEnvio
            : preg_replace('/@.+$/', '', $jidEnvio);

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

        // Evolution API: sempre precisa de 'number', seja grupo ou individual
        $apiUrl = config('services.evolution.url') . "/message/sendText/{$instance}";

        $payload = [
            'number' => $isGrupo ? $jidEnvio : $numeroEnvio,
            'text' => $respostaFinal,
        ];

        // Incluir JID no payload para maximizar compatibilidade (configurável)
        if ($isGrupo || config('app.always_include_jid')) {
            $payload['jid'] = $jidEnvio;
        }

        $sendResponse = Http::withHeaders([
            'apikey' => config('services.evolution.key'),
        ])->post($apiUrl, $payload);

        // Fallback para grupos: se falhar com 4xx, tentar novamente usando apenas 'jid'.
        if ($isGrupo && !$sendResponse->successful()) {
            Log::warning('Envio para grupo falhou; tentando fallback com jid.', [
                'status' => $sendResponse->status(),
                'body' => $sendResponse->body(),
                'jid' => $jidEnvio,
            ]);

            $fallbackPayload = [
                'jid' => $jidEnvio,
                'text' => $respostaFinal,
            ];

            $sendResponse = Http::withHeaders([
                'apikey' => config('services.evolution.key'),
            ])->post($apiUrl, $fallbackPayload);
        }

        Log::info('Resposta da API Evolution ao envio (job):', [
            'status' => $sendResponse->status(),
            'body' => $sendResponse->body(),
            'number' => $numeroEnvio,
            'jid' => $jidEnvio,
            'source' => 'agente-ia',
        ]);
    }
}

<?php

namespace App\Jobs;

// EXEMPLO: Como integrar os 8 pilares no ProcessWhatsappMessage existente
// Este arquivo mostra pseudocódigo com as integrações principais

use App\Services\AppointmentService;
use App\Services\LeadCaptureService;
use App\Services\FollowUpService;
use App\Services\ObjectionHandlerService;
use App\Services\ExplainableMatchingService;
use App\Services\EscalationService;
use App\Services\LgpdComplianceService;
use App\Services\ResponseValidationService;
use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappMessageIntegrationExample
{
    /**
     * EXEMPLO: Como integrar os 8 pilares
     * 
     * Coloque isso DENTRO do method handle() do ProcessWhatsappMessage.php
     * após você ter validado e normalizado a mensagem
     */
    
    public function exemploDeIntegracaoCompleta(
        int $empresaId,
        string $clienteId,
        string $pushName,
        string $mensagemRecebida,
        ?array $imovelSelecionado = null
    ): void {
        Log::info("=== EXEMPLO: Integração dos 8 Pilares ===");
        
        // ============================================================
        // PILAR 1: Captação de Lead Avançada
        // ============================================================
        
        // No início da conversa, capturar dados completos
        if ($etapa === 'qualificacao') {
            // Solicitar e registrar dados
            $lead = LeadCaptureService::capturarLead(
                empresaId: $empresaId,
                clienteJid: $clienteId,
                clienteNome: $pushName,
                dados: [
                    'renda_aproximada' => $this->extrairRenda($mensagemRecebida),
                    'tipo_financiamento' => $this->extrairFinanciamento($mensagemRecebida),
                    'prazo_desejado_anos' => $this->extrairPrazo($mensagemRecebida),
                    'urgencia' => $this->extrairUrgencia($mensagemRecebida),
                    'tem_pre_aprovacao' => $this->temPreAprovacao($mensagemRecebida),
                    'cidade_principal' => $this->extrairCidade($mensagemRecebida),
                    'bairros_nao_negociaveis' => $this->extrairBairros($mensagemRecebida),
                    'top_3_prioridades' => $this->extrairPrioridades($mensagemRecebida),
                    'consentimento_dados' => true, // Se autorizado
                ]
            );
            
            Log::info("Lead capturado", ['lead_id' => $lead->id]);
        }
        
        // ============================================================
        // PILAR 2: Tratamento de Objeções
        // ============================================================
        
        // Em qualquer etapa, detectar e tratar objeções
        $objecao = ObjectionHandlerService::detectarObjecao($mensagemRecebida);
        
        if ($objecao) {
            Log::info("Objeção detectada", [
                'tipo' => $objecao['tipo'],
                'detector' => $objecao['detector'],
            ]);
            
            // Gerar resposta ao playbook
            $resposta = ObjectionHandlerService::gerarRespostaObjecao($objecao);
            
            // Enviar
            EvolutionApiService::enviarMensagem($clienteId, $resposta);
            
            // Verificar se requer escalação para humano
            if (ObjectionHandlerService::sugerirEscalacao($objecao['tipo'])) {
                Log::info("Escalação sugerida por objeção complexa");
                // Ver PILAR 6 abaixo
            }
            
            return; // Não continuar com fluxo normal
        }
        
        // ============================================================
        // PILAR 3: Detecção de Intenção para Escalação
        // ============================================================
        
        // Detectar sinais de intenção forte (quero visitar, quero proposta, etc)
        $intencao = EscalationService::detectarIntencaoEscalacao($mensagemRecebida);
        
        if ($intencao) {
            Log::info("Intenção detectada", [
                'tipo' => $intencao['tipo'],
                'prioridade' => $intencao['prioridade'],
            ]);
            
            // Buscar dados do lead
            $leadData = LeadCapture::where('cliente_jid', $clienteId)
                ->where('empresa_id', $empresaId)
                ->first();
            
            // Escalar para humano
            $escalacao = EscalationService::escalar(
                empresaId: $empresaId,
                clienteJid: $clienteId,
                clienteNome: $pushName,
                leadData: $leadData->toArray(),
                tipoEscalacao: $intencao['tipo'],
                prioridade: $intencao['prioridade']
            );
            
            if ($escalacao['escalado']) {
                // Enviar mensagem ao cliente
                EvolutionApiService::enviarMensagem(
                    $clienteId,
                    $escalacao['mensagem_cliente']
                );
                
                // Log para corretor (integrar com CRM)
                Log::info("Caso escalado para corretor", [
                    'resumo' => $escalacao['resumo'],
                ]);
            }
            
            return;
        }
        
        // ============================================================
        // PILAR 4: Recomendações com Explicabilidade
        // ============================================================
        
        // Ao apresentar imóvel recomendado
        if ($imovelSelecionado) {
            // Calcular score
            $scoreDetalhes = MatchingEngine::calculateScore($imovelSelecionado, $slots);
            
            // Explicar POR QUÊ foi recomendado
            $explicacao = ExplainableMatchingService::explicarScore(
                imovel: $imovelSelecionado,
                slots: $slots,
                scoreTotal: $scoreDetalhes['score'],
                detalhesScore: $scoreDetalhes['detalhes']
            );
            
            Log::info("Score calculado", [
                'imovel_id' => $imovelSelecionado['id'],
                'score' => $scoreDetalhes['score'],
            ]);
            
            // Enviar com explicação
            EvolutionApiService::enviarMensagem($clienteId, $explicacao);
            
            // Registrar que gostou ou descartou
            if (strpos($mensagemRecebida, 'gostei') !== false) {
                LeadCaptureService::registrarInteracao(
                    $empresaId,
                    $clienteId,
                    $imovelSelecionado['id'],
                    'gostou'
                );
            }
        }
        
        // ============================================================
        // PILAR 5: Agendamento de Visita (Ponta a Ponta)
        // ============================================================
        
        if ($clientePedeuAgendar) {
            // Solicitar data/hora
            $dataAgendada = $this->parseDataAgendamento($mensagemRecebida);
            
            if ($dataAgendada) {
                // Criar agendamento
                $resultado = AppointmentService::agendarVisita(
                    empresaId: $empresaId,
                    clienteJid: $clienteId,
                    clienteNome: $pushName,
                    imovelId: $imovelSelecionado['id'],
                    imovelTitulo: $imovelSelecionado['titulo'],
                    dataAgendada: $dataAgendada,
                    observacoes: "Agendado via WhatsApp"
                );
                
                if ($resultado['sucesso']) {
                    // Enviar confirmação
                    EvolutionApiService::enviarMensagem(
                        $clienteId,
                        $resultado['mensagem']
                    );
                    
                    // Registrar no funil
                    MetricsService::registrarEventoFunil($clienteId, $empresaId, 'visita');
                    
                    Log::info("Visita agendada", [
                        'appointment_id' => $resultado['appointment_id'],
                    ]);
                } else {
                    EvolutionApiService::enviarMensagem(
                        $clienteId,
                        $resultado['erro']
                    );
                }
            }
            
            return;
        }
        
        // ============================================================
        // PILAR 6: Validação de Respostas Antes de Enviar
        // ============================================================
        
        // Antes de enviar QUALQUER resposta ao cliente
        $resposta = "...sua resposta gerada pelo bot...";
        
        $validacao = ResponseValidationService::validarResposta(
            resposta: $resposta,
            contexto: [
                'bairros_nao_negociaveis' => $lead->bairros_nao_negociaveis ?? [],
                'num_opcoes_apresentadas' => $numOpcoesMostradas,
            ],
            imovelData: $imovelSelecionado
        );
        
        if (!$validacao['pode_enviar']) {
            Log::error("Resposta bloqueada por validação", [
                'cliente_id' => $clienteId,
                'erros' => $validacao['erros'],
            ]);
            
            // Enviar resposta genérica segura
            EvolutionApiService::enviarMensagem(
                $clienteId,
                "Desculpe, tive um problema técnico. Um corretor vai te contatar em breve."
            );
            return;
        }
        
        if (!empty($validacao['avisos'])) {
            Log::warning("Resposta com avisos", $validacao['avisos']);
        }
        
        // Resposta OK, enviar normalmente
        EvolutionApiService::enviarMensagem($clienteId, $resposta);
        
        // ============================================================
        // PILAR 7: LGPD Compliance
        // ============================================================
        
        // No início, se novo cliente
        if ($leadNovo && !$lead->consentimento_dados) {
            $msgConsentimento = LgpdComplianceService::solicitarConsentimentoExplicito(
                $clienteId,
                $empresaId
            );
            
            EvolutionApiService::enviarMensagem($clienteId, $msgConsentimento);
            
            // Quando cliente autoriza
            if ($autorizou) {
                LgpdComplianceService::registrarConsentimento(
                    $clienteId,
                    $empresaId,
                    true,
                    'dados'
                );
            }
            
            return;
        }
        
        // Se cliente pede seus dados
        if ($mensagemRecebida === 'quero meus dados') {
            $export = LgpdComplianceService::exportarDadosCliente($clienteId, $empresaId);
            // Enviar arquivo JSON
            Log::info("Exportação de dados solicitada", ['cliente_id' => $clienteId]);
        }
        
        // Se cliente quer ser deletado
        if ($mensagemRecebida === 'deletar meus dados') {
            $resultado = LgpdComplianceService::deletarDadosCliente(
                $clienteId,
                $empresaId,
                'solicitacao_cliente'
            );
            
            EvolutionApiService::enviarMensagem($clienteId, $resultado['mensagem']);
        }
        
        // ============================================================
        // PILAR 8: Observabilidade (Funil + NPS)
        // ============================================================
        
        // Registrar eventos ao longo da conversa
        if ($etapa === 'qualificacao') {
            MetricsService::registrarEventoFunil($clienteId, $empresaId, 'qualificacao');
        }
        
        if ($etapa === 'resultado_busca') {
            MetricsService::registrarEventoFunil($clienteId, $empresaId, 'opcoes');
        }
        
        // Coletar NPS quando apropriado
        if ($clientePartiuConversa && !$converteu) {
            // Agendar coleta de NPS
            // (ou enviar diretamente)
            $perguntaNps = MetricsService::coletarNps($clienteId, $empresaId);
            EvolutionApiService::enviarMensagem($clienteId, $perguntaNps);
        }
        
        // Analisar por que não converteu
        if ($clientePartiuSemConvertir) {
            MetricsService::analisarNaoConversao($clienteId, $empresaId, [
                'objecoes_detectadas' => $objecoes ?? [],
                'num_opcoes_apresentadas' => $numOpcoesMostradas ?? 0,
            ]);
        }
        
        Log::info("=== FIM DA INTEGRAÇÃO DOS 8 PILARES ===");
    }
    
    // Helpers para parsing (simplificado, expandir conforme necessário)
    
    private function extrairRenda(string $msg): ?float
    {
        if (preg_match('/(\d+)\s*(mil|k)/i', $msg, $m)) {
            return (float)($m[1] * 1000);
        }
        if (preg_match('/R\$\s*(\d+[\.,\d]+)/i', $msg, $m)) {
            return (float)str_replace([',', '.'], '', $m[1]);
        }
        return null;
    }
    
    private function extrairFinanciamento(string $msg): ?string
    {
        if (stripos($msg, 'financiamento') !== false) return 'financiamento';
        if (stripos($msg, 'à vista') !== false || stripos($msg, 'dinheiro') !== false) return 'a_vista';
        if (stripos($msg, 'parcelado') !== false) return 'parcelado';
        if (stripos($msg, 'consórcio') !== false) return 'consorcio';
        return null;
    }
    
    private function extrairPrazo(string $msg): ?int
    {
        if (preg_match('/(\d+)\s*anos?/i', $msg, $m)) {
            return (int)$m[1];
        }
        return null;
    }
    
    private function extrairUrgencia(string $msg): string
    {
        if (preg_match('/(rápido|urgente|logo|logo)/i', $msg)) return 'alta';
        if (preg_match('/(quando der|sem pressa)/i', $msg)) return 'baixa';
        return 'media';
    }
    
    private function temPreAprovacao(string $msg): bool
    {
        return stripos($msg, 'pré-aprovado') !== false || 
               stripos($msg, 'pre-aprovado') !== false ||
               stripos($msg, 'aprovado') !== false;
    }
    
    private function extrairCidade(string $msg): ?string
    {
        // TODO: Integrar com lista de cidades
        if (stripos($msg, 'são paulo') !== false) return 'São Paulo';
        if (stripos($msg, 'sp') !== false && strlen($msg) < 50) return 'São Paulo';
        return null;
    }
    
    private function extrairBairros(string $msg): array
    {
        // TODO: Integrar com lista de bairros
        $bairrosKnown = ['Vila Mariana', 'Pinheiros', 'Leblon', 'Ipanema'];
        $found = [];
        foreach ($bairrosKnown as $bairro) {
            if (stripos($msg, $bairro) !== false) {
                $found[] = $bairro;
            }
        }
        return $found;
    }
    
    private function extrairPrioridades(string $msg): array
    {
        $prioridades = [];
        if (stripos($msg, 'pet') !== false || stripos($msg, 'cachorro') !== false) {
            $prioridades[] = 'pet_friendly';
        }
        if (stripos($msg, 'varanda') !== false) {
            $prioridades[] = 'varanda';
        }
        if (stripos($msg, 'suíte') !== false || stripos($msg, 'suite') !== false) {
            $prioridades[] = 'suite';
        }
        if (stripos($msg, '2 quartos') !== false || stripos($msg, 'dois quartos') !== false) {
            $prioridades[] = '2_quartos';
        }
        return array_slice($prioridades, 0, 3); // Top 3
    }
    
    private function parseDataAgendamento(string $msg): ?\DateTime
    {
        // TODO: Expandir parser de datas
        if (preg_match('/(\d{1,2})\/(\d{1,2})\s*às?\s*(\d{1,2}):(\d{2})/i', $msg, $m)) {
            return \Carbon\Carbon::createFromFormat(
                'd/m H:i',
                "{$m[1]}/{$m[2]} {$m[3]}:{$m[4]}"
            );
        }
        return null;
    }
}

/**
 * RESUMO DE MUDANÇAS NO PROCESSWHATSAPPMESSAGE.PHP
 * 
 * Adicione ANTES do method handle():
 * - use App\Services\LeadCaptureService;
 * - use App\Services\ObjectionHandlerService;
 * - use App\Services\ExplainableMatchingService;
 * - use App\Services\EscalationService;
 * - use App\Services\AppointmentService;
 * - use App\Services\ResponseValidationService;
 * - use App\Services\LgpdComplianceService;
 * - use App\Services\MetricsService;
 * 
 * Dentro do handle(), APÓS normalizar a mensagem, ANTES de responder:
 * 
 * 1. Se novo lead: Solicitar consentimento LGPD
 * 2. Capturar dados do lead (LeadCaptureService)
 * 3. Detectar objeção (ObjectionHandlerService)
 * 4. Detectar intenção de escalação (EscalationService)
 * 5. Gerar recomendações com score (MatchingEngine + ExplainableMatchingService)
 * 6. Validar resposta antes de enviar (ResponseValidationService)
 * 7. Agendar visita se necessário (AppointmentService)
 * 8. Registrar evento no funil (MetricsService)
 * 
 * ORDEM É IMPORTANTE:
 * LGPD → Lead Capture → Objeção Detection → Escalação → Recomendação → Validação → Envio → Métricas
 */

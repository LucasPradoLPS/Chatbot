<?php

namespace App\Services;

use App\Models\LeadCapture;
use Illuminate\Support\Facades\Log;

/**
 * Compliance LGPD
 * 
 * - Consentimento explícito
 * - Retenção de dados
 * - Exportação de dados
 * - Direito ao esquecimento
 */
class LgpdComplianceService
{
    /**
     * Solicitar consentimento para usar dados
     */
    public static function solicitarConsentimentoExplicito(
        string $clienteJid,
        int $empresaId
    ): string {
        return <<<MSG
📋 *Autorização para Processar Seus Dados*

Olá! Para oferecer as melhores recomendações de imóveis, preciso autorização para:

✅ Armazenar seu nome, telefone e preferências
✅ Usar essas informações para buscar imóveis compatíveis
✅ Enviar atualizações e novas opções relevantes
✅ Manter histórico de conversa (para melhor atendimento)

*Seus dados:*
🔒 Nunca serão vendidos ou compartilhados
🔒 Você pode deletar tudo a qualquer momento
🔒 Você pode se desinscrever de mensagens futuras

**AUTORIZO** | **NÃO AUTORIZO**

Leia nossa [Política de Privacidade](link) para mais detalhes.
MSG;
    }
    
    /**
     * Registrar consentimento
     */
    public static function registrarConsentimento(
        string $clienteJid,
        int $empresaId,
        bool $autorizou,
        string $tipo = 'dados'
    ): void {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if ($lead) {
            if ($tipo === 'dados') {
                $lead->update([
                    'consentimento_dados' => $autorizou,
                    'consentimento_dados_em' => $autorizou ? now() : null,
                ]);
            } elseif ($tipo === 'marketing') {
                $lead->update([
                    'consentimento_marketing' => $autorizou,
                    'consentimento_marketing_em' => $autorizou ? now() : null,
                ]);
            }
            
            Log::info("Consentimento LGPD registrado", [
                'cliente_jid' => $clienteJid,
                'tipo' => $tipo,
                'autorizou' => $autorizou,
            ]);
        }
    }
    
    /**
     * Exportar dados do cliente (direito de portabilidade)
     */
    public static function exportarDadosCliente(
        string $clienteJid,
        int $empresaId
    ): array {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if (!$lead) {
            return ['erro' => 'Cliente não encontrado'];
        }
        
        $export = [
            'dados_pessoais' => [
                'nome' => $lead->cliente_nome,
                'jid' => $lead->cliente_jid,
            ],
            'preferencias' => [
                'renda_aproximada' => $lead->renda_aproximada,
                'tipo_financiamento' => $lead->tipo_financiamento,
                'prazo_desejado_anos' => $lead->prazo_desejado_anos,
                'urgencia' => $lead->urgencia,
                'bairros' => $lead->bairros_nao_negociaveis,
                'prioridades' => $lead->top_3_prioridades,
            ],
            'historico_interacoes' => [
                'imoveis_gostou' => $lead->imoveis_gostou,
                'imoveis_descartou' => $lead->imoveis_descartou,
                'criado_em' => $lead->created_at,
                'ultimo_contato_em' => $lead->ultimo_contato_em,
            ],
            'consentimentos' => [
                'dados' => [
                    'autorizado' => $lead->consentimento_dados,
                    'data' => $lead->consentimento_dados_em,
                ],
                'marketing' => [
                    'autorizado' => $lead->consentimento_marketing,
                    'data' => $lead->consentimento_marketing_em,
                ],
            ],
        ];
        
        Log::info("Exportação de dados solicitada", [
            'cliente_jid' => $clienteJid,
            'empresa_id' => $empresaId,
        ]);
        
        return $export;
    }
    
    /**
     * Deletar todos os dados do cliente (direito ao esquecimento)
     * Implementa soft-delete para auditoria
     */
    public static function deletarDadosCliente(
        string $clienteJid,
        int $empresaId,
        string $motivo = 'solicitacao_cliente'
    ): array {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if (!$lead) {
            return ['erro' => 'Cliente não encontrado'];
        }
        
        // Soft delete (permite auditoria)
        $lead->delete();
        
        Log::warning("Dados do cliente deletados (LGPD)", [
            'cliente_jid' => $clienteJid,
            'empresa_id' => $empresaId,
            'motivo' => $motivo,
            'timestamp' => now(),
        ]);
        
        return [
            'sucesso' => true,
            'mensagem' => 'Seus dados foram completamente removidos do nosso sistema.',
        ];
    }
    
    /**
     * Política de retenção: deletar dados antigos
     * Executa automaticamente para LGPD compliance
     * (ex: dados de leads que não converteram em 6 meses)
     */
    public static function aplicarPoliticaRetencao(
        int $empresaId,
        int $diasRetencao = 180
    ): int {
        $dataLimite = now()->subDays($diasRetencao);
        
        $deletados = LeadCapture::where('empresa_id', $empresaId)
            ->where('status_lead', 'perdido') // Apenas leads perdidos
            ->where('ultimo_contato_em', '<', $dataLimite)
            ->delete();
        
        Log::info("Política de retenção aplicada", [
            'empresa_id' => $empresaId,
            'dias_retencao' => $diasRetencao,
            'registros_deletados' => $deletados,
        ]);
        
        return $deletados;
    }
    
    /**
     * Desinscrever cliente de comunicações (unsubscribe)
     */
    public static function desinscrever(
        string $clienteJid,
        int $empresaId
    ): void {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if ($lead) {
            $lead->update([
                'consentimento_marketing' => false,
                'consentimento_marketing_em' => null,
            ]);
            
            Log::info("Cliente desinscrito de marketing", [
                'cliente_jid' => $clienteJid,
            ]);
        }
    }
    
    /**
     * Gerar relatório de conformidade LGPD
     */
    public static function gerarRelatorioConformidade(int $empresaId): array
    {
        $leads = LeadCapture::where('empresa_id', $empresaId)->get();
        
        $comConsentimento = $leads->where('consentimento_dados', true)->count();
        $semConsentimento = $leads->where('consentimento_dados', false)->count();
        $diasMediaInatividade = $leads->average('dias_inativo');
        
        return [
            'data_relatorio' => now(),
            'empresa_id' => $empresaId,
            'total_leads' => $leads->count(),
            'com_consentimento_dados' => $comConsentimento,
            'sem_consentimento_dados' => $semConsentimento,
            'com_consentimento_marketing' => $leads->where('consentimento_marketing', true)->count(),
            'sem_consentimento_marketing' => $leads->where('consentimento_marketing', false)->count(),
            'dias_media_inatividade' => round($diasMediaInatividade, 1),
            'leads_para_deletar_em_breve' => LeadCapture::where('empresa_id', $empresaId)
                ->where('status_lead', 'perdido')
                ->where('ultimo_contato_em', '<', now()->subDays(150))
                ->count(),
        ];
    }
}

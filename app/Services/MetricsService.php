<?php

namespace App\Services;

use App\Models\ConversationAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Observabilidade e Métricas
 * 
 * Painel de funil: qualificação → opções → visita → proposta → venda
 * NPS/CSAT, análise de conversão, testes A/B
 */
class MetricsService
{
    /**
     * Registrar evento no funil
     */
    public static function registrarEventoFunil(
        string $clienteJid,
        int $empresaId,
        string $evento
    ): void {
        $analytics = ConversationAnalytics::firstOrCreate(
            ['cliente_jid' => $clienteJid, 'empresa_id' => $empresaId]
        );
        
        $eventosMap = [
            'qualificacao' => 'chegou_em_qualificacao_em',
            'opcoes' => 'recebeu_opcoes_em',
            'visita' => 'pediu_visita_em',
            'visitou' => 'visitou_em',
            'proposta' => 'recebeu_proposta_em',
            'converteu' => 'converteu_em',
        ];
        
        if (isset($eventosMap[$evento])) {
            $analytics->update([
                $eventosMap[$evento] => Carbon::now(),
            ]);
        }
    }
    
    /**
     * Obter métricas do funil
     */
    public static function obterMetricasFunil(int $empresaId): array
    {
        $analytics = ConversationAnalytics::where('empresa_id', $empresaId)->get();
        
        $total = $analytics->count();
        if ($total === 0) {
            return [];
        }
        
        return [
            'total_leads' => $total,
            'chegou_qualificacao' => $analytics->whereNotNull('chegou_em_qualificacao_em')->count(),
            'recebeu_opcoes' => $analytics->whereNotNull('recebeu_opcoes_em')->count(),
            'pediu_visita' => $analytics->whereNotNull('pediu_visita_em')->count(),
            'visitou' => $analytics->whereNotNull('visitou_em')->count(),
            'recebeu_proposta' => $analytics->whereNotNull('recebeu_proposta_em')->count(),
            'converteu' => $analytics->whereNotNull('converteu_em')->count(),
            'conversao_qualificacao_opcoes' => round(
                ($analytics->whereNotNull('recebeu_opcoes_em')->count() / $analytics->whereNotNull('chegou_em_qualificacao_em')->count() * 100) ?? 0, 1
            ),
            'conversao_opcoes_visita' => round(
                ($analytics->whereNotNull('pediu_visita_em')->count() / $analytics->whereNotNull('recebeu_opcoes_em')->count() * 100) ?? 0, 1
            ),
            'conversao_visita_proposta' => round(
                ($analytics->whereNotNull('recebeu_proposta_em')->count() / $analytics->whereNotNull('visitou_em')->count() * 100) ?? 0, 1
            ),
            'conversao_final' => round(
                ($analytics->whereNotNull('converteu_em')->count() / $total * 100) ?? 0, 1
            ),
        ];
    }
    
    /**
     * Coletar NPS/CSAT do cliente
     */
    public static function coletarNps(
        string $clienteJid,
        int $empresaId
    ): string {
        return <<<MSG
📊 *Sua Opinião é Importante!*

De 0 a 10, como foi sua experiência comigo?

0️⃣ 1️⃣ 2️⃣ 3️⃣ 4️⃣ 5️⃣ 6️⃣ 7️⃣ 8️⃣ 9️⃣ 🔟

(Clique no número)

*Depois você pode deixar um comentário, se quiser.*
MSG;
    }
    
    /**
     * Registrar NPS/CSAT
     */
    public static function registrarNps(
        string $clienteJid,
        int $empresaId,
        int $nps,
        ?string $feedback = null
    ): void {
        $analytics = ConversationAnalytics::firstOrCreate(
            ['cliente_jid' => $clienteJid, 'empresa_id' => $empresaId]
        );
        
        $analytics->update([
            'nps' => $nps,
            'feedback_texto' => $feedback,
        ]);
    }
    
    /**
     * Analisar motivo de não-conversão
     * Feito automaticamente quando cliente sai
     */
    public static function analisarNaoConversao(
        string $clienteJid,
        int $empresaId,
        array $contexto
    ): void {
        $motivo = self::detectarMotivo($clienteJid, $empresaId, $contexto);
        
        $analytics = ConversationAnalytics::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if ($analytics) {
            $analytics->update([
                'motivo_nao_conversao' => $motivo,
            ]);
        }
    }
    
    /**
     * Detectar por que não converteu
     */
    private static function detectarMotivo(
        string $clienteJid,
        int $empresaId,
        array $contexto
    ): string {
        // Lógica heurística
        $objecoes = $contexto['objecoes_detectadas'] ?? [];
        
        if (in_array('muito_caro', $objecoes)) {
            return 'preco';
        }
        if (in_array('bairro_longe', $objecoes)) {
            return 'bairro';
        }
        if (in_array('nao_eh_agora', $objecoes)) {
            return 'timing';
        }
        if (($contexto['num_opcoes_apresentadas'] ?? 0) === 0) {
            return 'falta_opcao';
        }
        
        return 'outro';
    }
    
    /**
     * Gerar relatório de A/B test
     * Ex: Copy A vs Copy B para mesmo imóvel
     */
    public static function registrarVariacaoAB(
        string $clienteJid,
        int $empresaId,
        string $experiencia, // 'A' ou 'B'
        string $copy,
        bool $converteu
    ): void {
        // TODO: Implementar tabela de ABTests para rastrear
        // Por enquanto apenas logging
    }
    
    /**
     * Obter copy que mais converte
     */
    public static function obterCopyMelhorPerformance(int $empresaId): array
    {
        // TODO: Analisar histórico de ABTests
        return [];
    }
    
    /**
     * Tempo médio de resposta
     */
    public static function calcularTempoMedioResposta(
        int $empresaId,
        ?Carbon $desde = null
    ): float {
        $desde = $desde ?? Carbon::now()->subDays(30);
        
        $analytics = ConversationAnalytics::where('empresa_id', $empresaId)
            ->where('created_at', '>=', $desde)
            ->whereNotNull('tempo_medio_resposta_seg')
            ->get();
        
        if ($analytics->isEmpty()) {
            return 0;
        }
        
        return round($analytics->avg('tempo_medio_resposta_seg'), 2);
    }
    
    /**
     * Taxa de satisfação geral
     */
    public static function obterTaxaSatisfacao(int $empresaId): array
    {
        $analytics = ConversationAnalytics::where('empresa_id', $empresaId)
            ->whereNotNull('nps')
            ->get();
        
        $total = $analytics->count();
        if ($total === 0) {
            return ['media' => 0, 'detalhes' => []];
        }
        
        $media = $analytics->avg('nps');
        
        // Classificação: 0-6 detratores, 7-8 neutros, 9-10 promotores
        $detratores = $analytics->where('nps', '<', 7)->count();
        $neutros = $analytics->whereBetween('nps', [7, 8])->count();
        $promotores = $analytics->where('nps', '>=', 9)->count();
        
        return [
            'media' => round($media, 1),
            'total_respostas' => $total,
            'detratores_pct' => round($detratores / $total * 100, 1),
            'neutros_pct' => round($neutros / $total * 100, 1),
            'promotores_pct' => round($promotores / $total * 100, 1),
        ];
    }
    
    /**
     * Dashboard consolidado
     */
    public static function obterDashboard(int $empresaId): array
    {
        return [
            'funil' => self::obterMetricasFunil($empresaId),
            'satisfacao' => self::obterTaxaSatisfacao($empresaId),
            'tempo_resposta_media_seg' => self::calcularTempoMedioResposta($empresaId),
            'data_atualizacao' => Carbon::now(),
        ];
    }
}

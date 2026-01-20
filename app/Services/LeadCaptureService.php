<?php

namespace App\Services;

use App\Models\LeadCapture;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LeadCaptureService
{
    /**
     * Criar ou atualizar lead com captação completa
     */
    public static function capturarLead(
        int $empresaId,
        string $clienteJid,
        string $clienteNome,
        array $dados = []
    ): LeadCapture {
        $lead = LeadCapture::firstOrCreate(
            ['cliente_jid' => $clienteJid, 'empresa_id' => $empresaId],
            [
                'cliente_nome' => $clienteNome,
                'status_lead' => 'novo',
            ]
        );
        
        // Atualizar com dados capturados
        $updates = [];
        
        if (isset($dados['renda_aproximada'])) {
            $updates['renda_aproximada'] = self::parseValor($dados['renda_aproximada']);
        }
        
        if (isset($dados['tipo_financiamento'])) {
            $updates['tipo_financiamento'] = $dados['tipo_financiamento'];
        }
        
        if (isset($dados['prazo_desejado_anos'])) {
            $updates['prazo_desejado_anos'] = (int)$dados['prazo_desejado_anos'];
        }
        
        if (isset($dados['urgencia'])) {
            $updates['urgencia'] = $dados['urgencia']; // alta / media / baixa
        }
        
        if (isset($dados['tem_pre_aprovacao'])) {
            $updates['tem_pre_aprovacao'] = $dados['tem_pre_aprovacao'];
            if ($dados['tem_pre_aprovacao']) {
                $updates['pre_aprovacao_valor'] = $dados['pre_aprovacao_valor'] ?? null;
                $updates['pre_aprovacao_banco'] = $dados['pre_aprovacao_banco'] ?? null;
            }
        }
        
        if (isset($dados['bairros_nao_negociaveis'])) {
            $updates['bairros_nao_negociaveis'] = is_array($dados['bairros_nao_negociaveis']) 
                ? $dados['bairros_nao_negociaveis'] 
                : [$dados['bairros_nao_negociaveis']];
        }
        
        if (isset($dados['cidade_principal'])) {
            $updates['cidade_principal'] = $dados['cidade_principal'];
        }
        
        if (isset($dados['top_3_prioridades'])) {
            $updates['top_3_prioridades'] = is_array($dados['top_3_prioridades']) 
                ? $dados['top_3_prioridades'] 
                : [$dados['top_3_prioridades']];
        }
        
        if (isset($dados['consentimento_dados'])) {
            $updates['consentimento_dados'] = true;
            $updates['consentimento_dados_em'] = Carbon::now();
        }
        
        if (isset($dados['consentimento_marketing'])) {
            $updates['consentimento_marketing'] = true;
            $updates['consentimento_marketing_em'] = Carbon::now();
        }
        
        $updates['ultimo_contato_em'] = Carbon::now();
        
        $lead->update($updates);
        
        Log::info("Lead capturado/atualizado", [
            'lead_id' => $lead->id,
            'cliente_jid' => $clienteJid,
            'dados' => array_keys($dados),
        ]);
        
        return $lead;
    }
    
    /**
     * Registrar interação com imóvel (gostou/descartou)
     */
    public static function registrarInteracao(
        int $empresaId,
        string $clienteJid,
        int $imovelId,
        string $tipo // 'gostou' ou 'descartou'
    ): void {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();
        
        if ($tipo === 'gostou') {
            $imoveis = $lead->imoveis_gostou ?? [];
            if (!in_array($imovelId, $imoveis)) {
                $imoveis[] = $imovelId;
                $lead->update(['imoveis_gostou' => $imoveis]);
            }
        } elseif ($tipo === 'descartou') {
            $imoveis = $lead->imoveis_descartou ?? [];
            if (!in_array($imovelId, $imoveis)) {
                $imoveis[] = $imovelId;
                $lead->update(['imoveis_descartou' => $imoveis]);
            }
        }
    }
    
    /**
     * Registrar preferência descartada (ex: "não quero térreo")
     */
    public static function registrarPreferenciaDescartada(
        int $empresaId,
        string $clienteJid,
        string $preferencia
    ): void {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();
        
        $prefs = $lead->preferencias_descartadas ?? [];
        if (!in_array($preferencia, $prefs)) {
            $prefs[] = $preferencia;
            $lead->update(['preferencias_descartadas' => $prefs]);
        }
    }
    
    /**
     * Obter leads inativos para follow-up
     * Retorna leads que sumiu há X dias e não recebeu follow-ups ainda
     */
    public static function obterLeadsParaFollowUp(int $empresaId, int $diasInativo = 2): array
    {
        $leads = LeadCapture::where('empresa_id', $empresaId)
            ->whereIn('status_lead', ['qualificado', 'em_busca'])
            ->where('ultimo_contato_em', '<', Carbon::now()->subDays($diasInativo))
            ->where(function ($q) {
                // Não recebeu ambos os follow-ups
                $q->where('enviou_follow_up_1', false)
                  ->orWhere('enviou_follow_up_2', false);
            })
            ->orderBy('ultimo_contato_em')
            ->limit(50)
            ->get();
        
        return $leads->toArray();
    }
    
    /**
     * Marcar que enviou follow-up 1
     */
    public static function marcarFollowUp1(int $leadId): void
    {
        LeadCapture::find($leadId)->update([
            'enviou_follow_up_1' => true,
            'proximo_follow_up_em' => Carbon::now()->addDays(3),
        ]);
    }
    
    /**
     * Marcar que enviou follow-up 2
     */
    public static function marcarFollowUp2(int $leadId): void
    {
        LeadCapture::find($leadId)->update([
            'enviou_follow_up_2' => true,
            'proximo_follow_up_em' => null,
        ]);
    }
    
    /**
     * Obter recomendações filtradas por preferências do lead
     */
    public static function obterRecomendacoesPersonalizadas(
        int $empresaId,
        string $clienteJid,
        int $limite = 5
    ): array {
        $lead = LeadCapture::where('cliente_jid', $clienteJid)
            ->where('empresa_id', $empresaId)
            ->first();
        
        if (!$lead) {
            return [];
        }
        
        // Construir slots a partir do lead capturado
        $slots = [
            'bairro_regiao' => $lead->bairros_nao_negociaveis ?? [],
            'faixa_valor_max' => $lead->renda_aproximada ? (int)($lead->renda_aproximada * 0.3) : 500000,
            'tags_prioridades' => $lead->top_3_prioridades ?? [],
        ];
        
        // Filtrar excluindo imoveis descartados e preferences descartadas
        // TODO: Implementar chamada ao MatchingEngine com filtros aplicados
        
        return [];
    }
    
    /**
     * Consentimento LGPD: permissão para usar dados
     */
    public static function solicitarConsentimento(
        int $empresaId,
        string $clienteJid
    ): string {
        return <<<MSG
📋 *Autorização de Dados*

Para oferecer as melhores opções de imóveis, preciso usar seus dados (nome, telefone, preferências) para buscar imóveis compatíveis.

Seus dados:
✅ Nunca serão compartilhados sem consentimento
✅ Podem ser deletados a qualquer momento
✅ Serão usados apenas para recomendações de imóveis

Autoriza? 
👍 Autorizo
👎 Não autorizo
MSG;
    }
    
    /**
     * Parse de valores (ex: "500 mil" -> 500000)
     */
    private static function parseValor(string $valor): float
    {
        $valor = strtolower($valor);
        $valor = preg_replace('/[^0-9.,]/', '', $valor);
        $valor = str_replace(',', '.', $valor);
        
        if (strpos($valor, '.') !== false) {
            // 500.000,00 -> remove primeira vírgula, mantém segunda
            $partes = explode('.', $valor);
            if (count($partes) > 2) {
                $valor = implode('', array_slice($partes, 0, -1)) . '.' . $partes[-1];
            }
        }
        
        return (float)$valor;
    }
}

<?php

namespace App\Services;

use App\Models\LeadCapture;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FollowUpService
{
    /**
     * Enviar follow-up 1: com base no que o cliente VIU
     * "Achei 2 opções que batem com o que você procurava..."
     */
    public static function enviarFollowUp1(LeadCapture $lead, int $empresaId): bool
    {
        try {
            // Buscar imoveis que o cliente viu mas não confirmou visita
            $imovisGostou = $lead->imoveis_gostou ?? [];
            
            if (empty($imovisGostou)) {
                // Se não viu nada, enviar com recomendações novas
                $mensagem = self::gerarMensagemFollowUpNovas($lead);
            } else {
                // Se viu, mencionar que achou similar
                $mensagem = self::gerarMensagemFollowUpSimilar($lead);
            }
            
            EvolutionApiService::enviarMensagem(
                $lead->cliente_jid,
                $mensagem,
                $empresaId
            );
            
            LeadCaptureService::marcarFollowUp1($lead->id);
            
            Log::info("Follow-up 1 enviado", ['lead_id' => $lead->id]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar follow-up 1", [
                'lead_id' => $lead->id,
                'erro' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Enviar follow-up 2: "última chance"
     * Mais direto, oferecendo atendimento humano
     */
    public static function enviarFollowUp2(LeadCapture $lead, int $empresaId): bool
    {
        try {
            $mensagem = <<<MSG
👋 {$lead->cliente_nome}, tudo bem?

Percebi que você saiu de conversa comigo. Tudo certo? 

Talvez tenha ficado com dúvida sobre:
❓ Financiamento
❓ Bairro
❓ Preço

Deixa eu chamar um **corretor de verdade** pra você. Pode ser?

📞 Atendimento humano
MSG;
            
            EvolutionApiService::enviarMensagem(
                $lead->cliente_jid,
                $mensagem,
                $empresaId
            );
            
            LeadCaptureService::marcarFollowUp2($lead->id);
            
            Log::info("Follow-up 2 enviado", ['lead_id' => $lead->id]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao enviar follow-up 2", [
                'lead_id' => $lead->id,
                'erro' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Gerar mensagem com opções novas (se cliente não viu nada relevante)
     */
    private static function gerarMensagemFollowUpNovas(LeadCapture $lead): string
    {
        $prioridades = !empty($lead->top_3_prioridades) 
            ? implode(', ', $lead->top_3_prioridades)
            : 'suas preferências';
        
        return <<<MSG
👀 {$lead->cliente_nome}, achei umas opções novas!

Procurando por: {$prioridades}

Mandei 2 imóveis que batem bastante com o que você quer. Quer ver?

🏠 Ver opções | ❌ Não, valeu
MSG;
    }
    
    /**
     * Gerar mensagem com imóvel similar ao que viu
     */
    private static function gerarMensagemFollowUpSimilar(LeadCapture $lead): string
    {
        return <<<MSG
🏠 {$lead->cliente_nome}, encontrei 2 imóveis muito parecidos!

Baseado no que você viu antes, achei opções com a mesma região, tamanho e preço.

Quer dar uma olhada? Levo apenas 10 segundos.

✅ Ver agora | ⏭️  Depois me chama
MSG;
    }
    
    /**
     * Automação: executar follow-ups pendentes
     * (Rode como scheduled job: php artisan schedule:work)
     */
    public static function procesarFollowUpsPendentes(int $empresaId): void
    {
        $leads = LeadCaptureService::obterLeadsParaFollowUp($empresaId, diasInativo: 2);
        
        Log::info("Processando follow-ups pendentes", [
            'empresa_id' => $empresaId,
            'total_leads' => count($leads),
        ]);
        
        foreach ($leads as $leadData) {
            $lead = LeadCapture::find($leadData['id']);
            
            if (!$lead->enviou_follow_up_1) {
                self::enviarFollowUp1($lead, $empresaId);
            } elseif (!$lead->enviou_follow_up_2) {
                // Enviar follow-up 2 após 3 dias do follow-up 1
                if ($lead->proximo_follow_up_em && $lead->proximo_follow_up_em <= Carbon::now()) {
                    self::enviarFollowUp2($lead, $empresaId);
                }
            }
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Thread;
use App\Models\InstanciaWhatsapp;
use App\Services\EventService;

class FollowupSchedulerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info('[FOLLOWUP-SCHEDULER] Job iniciado');

        // Buscar leads qualificados sem resposta há 2h
        $doisHorasAtras = now()->subHours(2);
        $threadsPendentes2h = Thread::where('crm_status', 'qualificado')
            ->where('ultimo_contato', '<=', $doisHorasAtras)
            ->where(function ($q) {
                $q->whereNull('proximo_followup')
                    ->orWhere('proximo_followup', '<=', now());
            })
            ->where('followup_tentativas', '<', 3)
            ->where('lgpd_opt_out', false)
            ->limit(50)
            ->get();

        foreach ($threadsPendentes2h as $thread) {
            $this->enviarFollowup2h($thread);
        }

        // Buscar leads em espera há 24h
        $umDiaAtras = now()->subHours(24);
        $threadsPendentes24h = Thread::where('crm_status', 'qualificado')
            ->where('ultimo_contato', '<=', $umDiaAtras)
            ->where('followup_tentativas', '>=', 1)
            ->where('followup_tentativas', '<', 3)
            ->where('lgpd_opt_out', false)
            ->limit(50)
            ->get();

        foreach ($threadsPendentes24h as $thread) {
            $this->enviarFollowup24h($thread);
        }

        Log::info('[FOLLOWUP-SCHEDULER] Job finalizado', [
            'processados_2h' => count($threadsPendentes2h),
            'processados_24h' => count($threadsPendentes24h),
        ]);
    }

    /**
     * Enviar mensagem leve após 2h
     */
    private function enviarFollowup2h(Thread $thread): void
    {
        try {
            $instancia = InstanciaWhatsapp::where('empresa_id', $thread->empresa_id)->first();
            if (!$instancia) {
                Log::warning('[FOLLOWUP] Instância não encontrada para empresa ' . $thread->empresa_id);
                return;
            }

            $mensagem = "👋 Oi! Tudo bem? Só vinha ver se você conseguiu analisar as opções que enviei. Qualquer dúvida, estou aqui! 😊";

            Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/message/sendText/{$instancia->instance_name}", [
                    'number' => $thread->numero_cliente,
                    'text' => $mensagem,
                ]);

            $thread->update([
                'proximo_followup' => now()->addHours(22),
                'followup_tentativas' => ($thread->followup_tentativas ?? 0) + 1,
            ]);

            EventService::followupSent($thread->empresa_id, $thread->numero_cliente, 'light', [
                'message' => 'follow-up 2h',
                'crm_status' => $thread->crm_status,
            ]);

            Log::info('[FOLLOWUP-2H] Mensagem enviada', [
                'numero_cliente' => $thread->numero_cliente,
                'crm_status' => $thread->crm_status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[FOLLOWUP-2H] Erro ao enviar', [
                'numero_cliente' => $thread->numero_cliente,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enviar mensagem de check-in após 24h
     */
    private function enviarFollowup24h(Thread $thread): void
    {
        try {
            $instancia = InstanciaWhatsapp::where('empresa_id', $thread->empresa_id)->first();
            if (!$instancia) {
                Log::warning('[FOLLOWUP] Instância não encontrada para empresa ' . $thread->empresa_id);
                return;
            }

            $mensagem = "Oi! Percebi que você estava procurando imóvel. Posso ajustar sua busca? Talvez ajuste no valor, bairro ou outros critérios. Me avisa! 🏠";

            Http::withHeaders(['apikey' => config('services.evolution.key')])
                ->post(config('services.evolution.url') . "/message/sendText/{$instancia->instance_name}", [
                    'number' => $thread->numero_cliente,
                    'text' => $mensagem,
                ]);

            $thread->update([
                'proximo_followup' => now()->addHours(72),
                'followup_tentativas' => ($thread->followup_tentativas ?? 0) + 1,
            ]);

            EventService::followupSent($thread->empresa_id, $thread->numero_cliente, 'checkin24h', [
                'message' => 'check-in 24h com oferta de ajuste',
                'crm_status' => $thread->crm_status,
            ]);

            Log::info('[FOLLOWUP-24H] Mensagem enviada', [
                'numero_cliente' => $thread->numero_cliente,
                'crm_status' => $thread->crm_status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[FOLLOWUP-24H] Erro ao enviar', [
                'numero_cliente' => $thread->numero_cliente,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}

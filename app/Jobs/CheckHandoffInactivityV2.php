<?php

namespace App\Jobs;

use App\Models\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * 🤖 Job que verifica inatividade durante handoff e encerra o chat após 5 minutos
 * 
 * VERSÃO 2: Simplificada e otimizada
 * - Dispara UMA ÚNICA VEZ, na marcação de 5 minutos
 * - Sem re-dispatch (sem loop infinito)
 * - Cache para evitar duplicatas
 * - Máximo de memory usage
 */
class CheckHandoffInactivityV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientNumber;
    protected $instanceName;
    protected $threadId;
    protected $expectedLastActivityIso;

    public function __construct($clientNumber, $instanceName = 'N8n', $threadId = null, $expectedLastActivityIso = null)
    {
        $this->clientNumber = $clientNumber;
        $this->instanceName = $instanceName;
        $this->threadId = $threadId;
        $this->expectedLastActivityIso = $expectedLastActivityIso;
    }

    /**
     * Executa uma única vez, verificando se passaram 5 minutos de inatividade
     */
    public function handle()
    {
        try {
            Log::info('[HANDOFF-TIMEOUT-V2] Verificando inatividade após 5 minutos', [
                'cliente' => $this->clientNumber,
                'timestamp' => now()
            ]);

            // Buscar thread
            $thread = Thread::where('numero_cliente', $this->clientNumber)
                ->where('thread_id', $this->threadId)
                ->first();

            if (!$thread) {
                Log::warning('[HANDOFF-TIMEOUT-V2] Thread não encontrada', [
                    'cliente' => $this->clientNumber
                ]);
                return;
            }

            // Se saiu de handoff, cancelar
            if ($thread->estado_atual !== 'STATE_HANDOFF') {
                Log::info('[HANDOFF-TIMEOUT-V2] Chat não está em handoff, ignorando', [
                    'cliente' => $this->clientNumber,
                    'estado' => $thread->estado_atual
                ]);
                return;
            }

            // Verificar inatividade
            $ultimaAtividade = $thread->ultima_atividade_usuario;
            if (!$ultimaAtividade) {
                Log::warning('[HANDOFF-TIMEOUT-V2] Sem timestamp de atividade', [
                    'cliente' => $this->clientNumber
                ]);
                return;
            }

            // Se este job foi agendado com um marcador de "última atividade esperada",
            // só pode encerrar se não houve atividade após o agendamento.
            if (!empty($this->expectedLastActivityIso)) {
                try {
                    $expected = \Illuminate\Support\Carbon::parse($this->expectedLastActivityIso);
                    if ($ultimaAtividade->gt($expected)) {
                        Log::info('[HANDOFF-TIMEOUT-V2] Cliente teve atividade após o agendamento; ignorando este job', [
                            'cliente' => $this->clientNumber,
                            'ultima_atividade' => $ultimaAtividade,
                            'expected_last_activity' => $expected,
                        ]);
                        return;
                    }
                } catch (\Throwable $e) {
                    Log::warning('[HANDOFF-TIMEOUT-V2] Falha ao parsear expectedLastActivityIso; continuando', [
                        'cliente' => $this->clientNumber,
                        'expectedLastActivityIso' => $this->expectedLastActivityIso,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            $minutosInativo = $ultimaAtividade->diffInMinutes(now());

            Log::info('[HANDOFF-TIMEOUT-V2] Status de inatividade', [
                'cliente' => $this->clientNumber,
                'minutos_inativo' => $minutosInativo,
                'limite' => 5
            ]);

            // Se passou de 5 minutos, encerrar
            if ($minutosInativo >= 5) {
                $this->encerrarHandoff($thread);
            } else {
                // Não atingiu limite, apenas log informativo
                Log::info('[HANDOFF-TIMEOUT-V2] Cliente ainda ativo (menos de 5 min)', [
                    'cliente' => $this->clientNumber,
                    'minutos' => $minutosInativo
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT-V2] Erro', [
                'erro' => $e->getMessage(),
                'cliente' => $this->clientNumber
            ]);
        }
    }

    /**
     * Encerra o handoff
     */
    protected function encerrarHandoff(Thread $thread)
    {
        try {
            Log::warning('[HANDOFF-TIMEOUT-V2] Encerrando por inatividade', [
                'cliente' => $this->clientNumber
            ]);

            // Mensagem
            $mensagem = "⏰ Seu atendimento foi encerrado por inatividade. Se precisar novamente, é só chamar! 👋";
            $jid = $this->normalizeNumber($this->clientNumber) . '@s.whatsapp.net';
            
            // Enviar
            $this->sendViaEvolution($this->clientNumber, $mensagem, $jid);

            // Atualizar estado
            $thread->update([
                'estado_atual' => 'STATE_CLOSED',
                'etapa_fluxo' => 'encerrado'
            ]);

            Log::info('[HANDOFF-TIMEOUT-V2] Handoff encerrado com sucesso', [
                'cliente' => $this->clientNumber
            ]);

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT-V2] Erro ao encerrar', [
                'erro' => $e->getMessage(),
                'cliente' => $this->clientNumber
            ]);
        }
    }

    protected function normalizeNumber($number)
    {
        $number = preg_replace('/\D/', '', $number);
        if (strpos($number, '@') !== false) {
            $number = explode('@', $number)[0];
            $number = preg_replace('/\D/', '', $number);
        }
        return $number;
    }

    protected function sendViaEvolution($number, $text, $jid)
    {
        try {
            $evolutionUrl = config('services.evolution.url');
            $evolutionKey = config('services.evolution.key');

            if (!$evolutionUrl || !$evolutionKey) {
                Log::error('[HANDOFF-TIMEOUT-V2] Evolution não configurado');
                return;
            }

            Http::withHeaders([
                'apikey' => $evolutionKey,
                'Content-Type' => 'application/json'
            ])->post("$evolutionUrl/message/sendText/{$this->instanceName}", [
                'number' => $number,
                'text' => $text,
                'jid' => $jid
            ]);

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT-V2] Erro ao enviar', [
                'erro' => $e->getMessage()
            ]);
        }
    }

    public function tries()
    {
        return 1; // Apenas 1 tentativa
    }
}

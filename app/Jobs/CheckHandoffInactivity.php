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
 * Disparado quando o estado muda para STATE_HANDOFF
 */
class CheckHandoffInactivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientNumber;
    protected $instanceName;
    protected $threadId;
    protected $inactivityMinutes;

    /**
     * @param string $clientNumber Número do cliente
     * @param string $instanceName Nome da instância Evolution
     * @param string|null $threadId ID da thread
     * @param int $inactivityMinutes Minutos de inatividade antes de encerrar (padrão: 5)
     */
    public function __construct($clientNumber, $instanceName = 'N8n', $threadId = null, $inactivityMinutes = 5)
    {
        $this->clientNumber = $clientNumber;
        $this->instanceName = $instanceName;
        $this->threadId = $threadId;
        $this->inactivityMinutes = $inactivityMinutes;
    }

    /**
     * Executa o job
     */
    public function handle()
    {
        try {
            // ⚠️ PROTEÇÃO: Se já foi processado antes, não processar de novo
            $cacheKey = 'timeout_check_' . $this->clientNumber . '_' . ($this->threadId ?? 'no_thread');
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                Log::info('[HANDOFF-TIMEOUT] Verificação já foi processada, ignorando duplicata', [
                    'cliente' => $this->clientNumber
                ]);
                return;
            }
            
            // Marcar como processado por 10 minutos
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, \Illuminate\Support\Carbon::now()->addMinutes(10));

            Log::info('[HANDOFF-TIMEOUT] Verificando inatividade', [
                'cliente' => $this->clientNumber,
                'minutos' => $this->inactivityMinutes,
                'timestamp' => now()
            ]);

            // Buscar a thread
            $thread = Thread::where('numero_cliente', $this->clientNumber)
                ->where('thread_id', $this->threadId)
                ->first();

            if (!$thread) {
                Log::warning('[HANDOFF-TIMEOUT] Thread não encontrada', [
                    'cliente' => $this->clientNumber,
                    'thread_id' => $this->threadId
                ]);
                return;
            }

            // Verificar se ainda está em handoff
            if ($thread->estado_atual !== 'STATE_HANDOFF') {
                Log::info('[HANDOFF-TIMEOUT] Chat saiu do estado HANDOFF, cancelando timeout', [
                    'cliente' => $this->clientNumber,
                    'estado_atual' => $thread->estado_atual
                ]);
                return;
            }

            // Verificar última atividade do usuário
            $ultimaAtividade = $thread->ultima_atividade_usuario;
            
            if (!$ultimaAtividade) {
                Log::warning('[HANDOFF-TIMEOUT] Última atividade não registrada', [
                    'cliente' => $this->clientNumber
                ]);
                return;
            }

            $minutosInativo = $ultimaAtividade->diffInMinutes(now());

            Log::info('[HANDOFF-TIMEOUT] Status da inatividade', [
                'cliente' => $this->clientNumber,
                'minutos_inativo' => $minutosInativo,
                'limite' => $this->inactivityMinutes,
                'ultima_atividade' => $ultimaAtividade
            ]);

            // Se passou do tempo de inatividade, encerrar
            if ($minutosInativo >= $this->inactivityMinutes) {
                $this->encerrarHandoff($thread);
            } else {
                // ⚠️ NÃO re-agendar - evita loop infinito
                // O timeout será verificado apenas uma vez (na hora agendada)
                Log::info('[HANDOFF-TIMEOUT] Inatividade ainda não atingiu o limite, aguardando próxima verificação', [
                    'cliente' => $this->clientNumber,
                    'minutos_inativo' => $minutosInativo,
                    'limite' => $this->inactivityMinutes
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT] Erro ao verificar inatividade', [
                'erro' => $e->getMessage(),
                'cliente' => $this->clientNumber,
                'stack' => $e->getTraceAsString()
            ]);

            // Retry em 2 minutos
            $this->release(120);
        }
    }

    /**
     * Encerra o handoff após inatividade
     */
    protected function encerrarHandoff(Thread $thread)
    {
        try {
            Log::warning('[HANDOFF-TIMEOUT] Encerrando handoff por inatividade!', [
                'cliente' => $this->clientNumber,
                'thread_id' => $this->threadId
            ]);

            // Mensagem de encerramento
            $mensagemEncerramento = "⏰ Seu atendimento foi encerrado por inatividade. Se precisar de ajuda novamente, é só chamar! 👋";

            // Enviar mensagem de encerramento via Evolution
            $jid = $this->normalizeNumber($this->clientNumber) . '@s.whatsapp.net';
            $this->sendViaEvolution($this->clientNumber, $mensagemEncerramento, $jid);

            // Atualizar estado da thread para encerrado
            $thread->update([
                'estado_atual' => 'STATE_CLOSED',
                'etapa_fluxo' => 'encerrado',
                'metadata->handoff_inativo_encerrado' => true,
                'metadata->handoff_inativo_timestamp' => now()
            ]);

            // Log de auditoria
            Log::info('[HANDOFF-TIMEOUT] Handoff encerrado com sucesso', [
                'cliente' => $this->clientNumber,
                'motivo' => 'inatividade',
                'minutos_limite' => $this->inactivityMinutes
            ]);

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT] Erro ao encerrar handoff', [
                'erro' => $e->getMessage(),
                'cliente' => $this->clientNumber
            ]);
        }
    }

    /**
     * Normaliza o número do cliente para formato Evolution
     */
    protected function normalizeNumber($number)
    {
        $number = preg_replace('/\D/', '', $number);
        
        if (strpos($number, '@') !== false) {
            $number = explode('@', $number)[0];
            $number = preg_replace('/\D/', '', $number);
        }

        return $number;
    }

    /**
     * Envia mensagem via Evolution API
     */
    protected function sendViaEvolution($number, $text, $jid)
    {
        try {
            $evolutionUrl = config('services.evolution.url');
            $evolutionKey = config('services.evolution.key');

            if (!$evolutionUrl || !$evolutionKey) {
                Log::error('[HANDOFF-TIMEOUT] Configurações Evolution não definidas');
                return null;
            }

            $payload = [
                'number' => $number,
                'text' => $text,
                'jid' => $jid
            ];

            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
                'Content-Type' => 'application/json'
            ])->post("$evolutionUrl/message/sendText/{$this->instanceName}", $payload);

            return [
                'status' => $response->status(),
                'body' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('[HANDOFF-TIMEOUT] Erro ao enviar via Evolution', [
                'erro' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Tempo máximo de retry
     */
    public function retryUntil()
    {
        return now()->addHours(1);
    }

    /**
     * Número máximo de tentativas
     */
    public function tries()
    {
        return 5;
    }
}

<?php

namespace App\Jobs;

use App\Models\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendHumanHandoffMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    // NOTE: these are nullable with defaults to avoid "typed property must not be accessed" when
    // older queued payloads don't hydrate them (visibility mismatch). We'll resolve values safely.
    protected ?string $clientNumber = null;
    protected ?string $instanceName = null;
    protected ?string $threadId = null;

    public function __construct(string $clientNumber, string $instanceName, ?string $threadId = null)
    {
        $this->clientNumber = $clientNumber;
        $this->instanceName = $instanceName;
        $this->threadId = $threadId;
    }

    /**
     * Resolve properties from serialized payloads even if visibility changed.
     * Casting the object to array exposes mangled keys for protected/private properties.
     */
    protected function resolveSerializedString(string $propertySuffix): ?string
    {
        $vars = (array) $this;
        foreach ($vars as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (substr($key, -strlen($propertySuffix)) !== $propertySuffix) {
                continue;
            }

            if ($value === null) {
                return null;
            }

            return is_string($value) ? $value : (string) $value;
        }

        return null;
    }

    public function handle(): void
    {
        try {
            $clientNumber = $this->clientNumber ?? $this->resolveSerializedString('clientNumber');
            $instanceName = $this->instanceName ?? $this->resolveSerializedString('instanceName');
            $threadId = $this->threadId ?? $this->resolveSerializedString('threadId');

            if (!$clientNumber || !$instanceName) {
                Log::error('[HANDOFF] Job humano sem dados obrigatórios; descartando', [
                    'clientNumber' => $clientNumber,
                    'instanceName' => $instanceName,
                    'threadId' => $threadId,
                ]);
                return;
            }

            Log::info('[HANDOFF] Executando mensagem humana (Lucas)', [
                'numero' => $clientNumber,
                'instancia' => $instanceName,
                'thread_id' => $threadId,
                'executado_em' => now()->toDateTimeString(),
            ]);

            if ($threadId) {
                $thread = Thread::where('thread_id', $threadId)->first();
                if ($thread && ($thread->estado_atual ?? null) !== 'STATE_HANDOFF') {
                    Log::info('[HANDOFF] Job humano cancelado: cliente saiu do handoff', [
                        'numero' => $clientNumber,
                        'thread_id' => $threadId,
                        'estado_atual' => $thread->estado_atual,
                    ]);
                    return;
                }
            }

            $mensagem = "Meu nome é Lucas e darei continuidade ao seu atendimento. Como posso ajudá-lo?";

            $numero = $this->normalizeNumber($clientNumber);
            $jid = $numero . '@s.whatsapp.net';

            $response = $this->sendViaEvolution($numero, $mensagem, $jid);

            if ($response && in_array($response['status'], [200, 201], true)) {
                Log::info('[HANDOFF] Mensagem humana enviada com sucesso', [
                    'numero' => $numero,
                    'status' => $response['status'],
                    'message_id' => $response['key']['id'] ?? null,
                ]);

                if ($threadId) {
                    $thread = Thread::where('thread_id', $threadId)->first();
                    if ($thread) {
                        $thread->update([
                            'metadata->handoff_lucas_enviada' => true,
                            'metadata->handoff_lucas_timestamp' => now(),
                        ]);
                    }
                }
            } else {
                Log::warning('[HANDOFF] Falha ao enviar mensagem humana', [
                    'numero' => $numero,
                    'response' => $response,
                ]);
            }

        } catch (\Throwable $e) {
            $clientNumber = $this->clientNumber ?? $this->resolveSerializedString('clientNumber');
            Log::error('[HANDOFF] Erro ao executar job humano', [
                'numero' => $clientNumber,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    protected function normalizeNumber(string $number): string
    {
        if (str_contains($number, '@')) {
            $number = explode('@', $number)[0];
        }

        return preg_replace('/\D/', '', $number) ?? '';
    }

    protected function sendViaEvolution(string $number, string $text, string $jid): ?array
    {
        try {
            $url = config('services.evolution.url');
            $key = config('services.evolution.key');

            if (!$url || !$key) {
                Log::error('[HANDOFF] Evolution não configurado corretamente');
                return null;
            }

            $payload = [
                'number' => $number,
                'text'   => $text,
                'jid'    => $jid,
            ];

            $response = Http::withHeaders([
                'apikey' => $key,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post(rtrim($url, '/') . "/message/sendText/{$this->instanceName}", $payload);

            return [
                'status' => $response->status(),
                'body'   => $response->json(),
                'key'    => $response->json('key') ?? [],
            ];

        } catch (\Throwable $e) {
            Log::error('[HANDOFF] Erro ao chamar Evolution API', [
                'erro' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(5);
    }
}

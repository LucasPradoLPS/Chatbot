<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * ResilientHttpService - Requisições HTTP com retry inteligente
 * 
 * Features:
 * - Retry com backoff exponencial
 * - Circuit breaker para APIs externas
 * - Timeout inteligente
 * - Fallback gracioso
 * - Métricas de confiabilidade
 */
class ResilientHttpService
{
    private const MAX_RETRIES = 3;
    private const INITIAL_BACKOFF = 1; // segundos
    private const MAX_BACKOFF = 32; // segundos
    private const CIRCUIT_BREAKER_THRESHOLD = 5; // erros antes de abrir
    private const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutos

    /**
     * Fazer requisição GET com retry automático
     */
    public static function getWithRetry(string $url, array $headers = [], int $timeout = 30): ?array
    {
        return self::executeWithRetry(function () use ($url, $headers, $timeout) {
            return Http::withHeaders($headers)
                ->timeout($timeout)
                ->get($url);
        }, "GET", $url);
    }

    /**
     * Fazer requisição POST com retry automático
     */
    public static function postWithRetry(string $url, array $data = [], array $headers = [], int $timeout = 30): ?array
    {
        return self::executeWithRetry(function () use ($url, $data, $headers, $timeout) {
            return Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($url, $data);
        }, "POST", $url);
    }

    /**
     * Executar com retry e circuit breaker
     */
    private static function executeWithRetry(callable $request, string $method, string $url): ?array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $circuitKey = "circuit_breaker:{$host}";
        
        // Verificar se circuit está aberto
        if (cache()->get($circuitKey) === 'open') {
            Log::warning("[CIRCUIT_BREAKER] Circuito aberto para host", ['host' => $host]);
            return null;
        }

        $lastError = null;
        $errorCount = 0;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                Log::debug("[HTTP] Tentativa {$attempt}/{self::MAX_RETRIES}", [
                    'method' => $method,
                    'url' => $url
                ]);

                $response = $request();

                if ($response->successful()) {
                    // Reset circuit breaker se sucesso
                    cache()->forget($circuitKey);
                    self::recordSuccess($host);
                    
                    Log::debug("[HTTP] Sucesso", [
                        'method' => $method,
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);

                    return $response->json();
                }

                // Status 4xx geralmente não faz sentido retry
                if ($response->status() >= 400 && $response->status() < 500) {
                    Log::warning("[HTTP] Erro cliente não recuperável", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }

                $lastError = "HTTP {$response->status()}";
                $errorCount++;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $errorCount++;
                
                Log::debug("[HTTP] Exceção na tentativa {$attempt}", [
                    'error' => $e->getMessage(),
                    'url' => $url
                ]);
            }

            // Não fazer sleep na última tentativa
            if ($attempt < self::MAX_RETRIES) {
                $backoff = min(
                    self::INITIAL_BACKOFF * (2 ** ($attempt - 1)),
                    self::MAX_BACKOFF
                );
                
                // Adicionar jitter (até 50% de variação)
                $jitter = $backoff * 0.5 * (mt_rand() / mt_getrandmax());
                $sleepTime = $backoff + $jitter;

                Log::debug("[HTTP] Aguardando antes de retry", [
                    'backoff' => round($sleepTime, 2),
                    'tentativa' => $attempt
                ]);

                sleep((int) $sleepTime);
            }
        }

        // Abrir circuit breaker se todos os erros falharam
        if ($errorCount >= self::CIRCUIT_BREAKER_THRESHOLD) {
            cache()->put($circuitKey, 'open', self::CIRCUIT_BREAKER_TIMEOUT);
            Log::error("[CIRCUIT_BREAKER] Circuito aberto por muitos erros", [
                'host' => $host,
                'erros' => $errorCount
            ]);
        }

        Log::error("[HTTP] Todas as tentativas falharam", [
            'method' => $method,
            'url' => $url,
            'last_error' => $lastError,
            'tentativas' => self::MAX_RETRIES
        ]);

        return null;
    }

    /**
     * Registrar sucesso para monitoramento
     */
    private static function recordSuccess(string $host): void
    {
        $key = "http:success:{$host}";
        $count = cache()->get($key, 0) + 1;
        cache()->put($key, $count, now()->addHour());
    }

    /**
     * Obter estatísticas de confiabilidade
     */
    public static function getReliabilityStats(string $host): array
    {
        $successKey = "http:success:{$host}";
        $successCount = cache()->get($successKey, 0);
        
        return [
            'host' => $host,
            'success_count' => $successCount,
            'circuit_status' => cache()->get("circuit_breaker:{$host}", 'closed'),
            'timestamp' => now()->toIso8601String()
        ];
    }
}

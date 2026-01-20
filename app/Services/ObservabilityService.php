<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ObservabilityService - Logging estruturado e monitoramento
 * 
 * Features:
 * - Trace ID para rastreabilidade end-to-end
 * - Timing metrics automático
 * - Structured logging em JSON
 * - Performance insights
 * - Error context enriquecido
 */
class ObservabilityService
{
    private static array $traceContext = [];
    private static array $timings = [];
    private static string $requestId = '';

    /**
     * Inicializar contexto de rastreamento
     */
    public static function initializeContext(array $context = []): void
    {
        self::$requestId = (string) Str::uuid();
        self::$traceContext = array_merge([
            'request_id' => self::$requestId,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context);

        Log::info('[TRACE] Contexto inicializado', self::$traceContext);
    }

    /**
     * Adicionar informação ao contexto
     */
    public static function addContext(string $key, $value): void
    {
        self::$traceContext[$key] = $value;
    }

    /**
     * Obter trace ID
     */
    public static function getTraceId(): string
    {
        return self::$requestId ?: (string) Str::uuid();
    }

    /**
     * Registrar início de operação (retorna marca de tempo para medir duração)
     */
    public static function startTiming(string $operation): string
    {
        $mark = "timing:{$operation}:" . uniqid();
        self::$timings[$mark] = microtime(true);
        
        Log::debug("[TIMING] Iniciando", [
            'operation' => $operation,
            'mark' => $mark,
            'request_id' => self::$requestId
        ]);

        return $mark;
    }

    /**
     * Registrar fim de operação e retornar duração em ms
     */
    public static function endTiming(string $mark): float
    {
        if (!isset(self::$timings[$mark])) {
            Log::warning("[TIMING] Mark não encontrado", ['mark' => $mark]);
            return 0;
        }

        $duration = (microtime(true) - self::$timings[$mark]) * 1000; // em ms
        unset(self::$timings[$mark]);

        preg_match('/^timing:(.+):/', $mark, $matches);
        $operation = $matches[1] ?? 'unknown';

        Log::debug("[TIMING] Concluído", [
            'operation' => $operation,
            'duration_ms' => round($duration, 2),
            'request_id' => self::$requestId
        ]);

        return $duration;
    }

    /**
     * Log estruturado de sucesso
     */
    public static function logSuccess(string $operation, array $details = []): void
    {
        Log::info("[SUCCESS] {$operation}", array_merge([
            'request_id' => self::$requestId,
            'timestamp' => now()->toIso8601String(),
        ], $details, self::$traceContext));
    }

    /**
     * Log estruturado de erro
     */
    public static function logError(string $operation, \Throwable $exception, array $details = []): void
    {
        Log::error("[ERROR] {$operation}", array_merge([
            'request_id' => self::$requestId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
        ], $details, self::$traceContext));
    }

    /**
     * Log estruturado de warning
     */
    public static function logWarning(string $operation, array $details = []): void
    {
        Log::warning("[WARNING] {$operation}", array_merge([
            'request_id' => self::$requestId,
            'timestamp' => now()->toIso8601String(),
        ], $details, self::$traceContext));
    }

    /**
     * Registrar métrica de performance
     */
    public static function recordMetric(string $name, float $value, array $tags = []): void
    {
        Log::info("[METRIC] {$name}", array_merge([
            'request_id' => self::$requestId,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toIso8601String(),
        ], self::$traceContext));
    }

    /**
     * Registrar evento importante
     */
    public static function recordEvent(string $name, array $data = []): void
    {
        Log::info("[EVENT] {$name}", array_merge([
            'request_id' => self::$requestId,
            'timestamp' => now()->toIso8601String(),
        ], $data, self::$traceContext));
    }

    /**
     * Gerar relatório de contexto para troubleshooting
     */
    public static function getContextReport(): array
    {
        return [
            'trace_id' => self::$requestId,
            'context' => self::$traceContext,
            'pending_timings' => count(self::$timings),
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Resetar contexto (para fins de testes)
     */
    public static function reset(): void
    {
        self::$traceContext = [];
        self::$timings = [];
        self::$requestId = '';
    }
}

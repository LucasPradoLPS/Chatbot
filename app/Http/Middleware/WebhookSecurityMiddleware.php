<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\InputValidationService;
use App\Services\ObservabilityService;

class WebhookSecurityMiddleware
{
    /**
     * Validações de segurança para webhooks
     */
    public function handle(Request $request, Closure $next)
    {
        // Inicializar contexto de observabilidade
        ObservabilityService::initializeContext([
            'endpoint' => $request->path(),
            'method' => $request->method(),
        ]);

        // 1. Validar Content-Type
        if ($request->getContentType() !== 'json') {
            Log::warning('[SECURITY] Content-Type inválido', [
                'content_type' => $request->getContentType()
            ]);
            return response()->json(['error' => 'Content-Type deve ser application/json'], 415);
        }

        // 2. Validar tamanho do payload (máx 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($request->server('CONTENT_LENGTH', 0) > $maxSize) {
            Log::warning('[SECURITY] Payload muito grande', [
                'size' => $request->server('CONTENT_LENGTH')
            ]);
            return response()->json(['error' => 'Payload muito grande'], 413);
        }

        // 3. Rate limiting global por IP
        $clientIp = $request->ip();
        $globalKey = "ratelimit:ip:{$clientIp}";
        $globalLimit = \Illuminate\Support\Facades\RateLimiter::attempt(
            $globalKey,
            100, // 100 requisições
            function () {},
            60 // por minuto
        );

        if (!$globalLimit) {
            Log::warning('[SECURITY] Rate limit global excedido', ['ip' => $clientIp]);
            return response()->json(['error' => 'Rate limit excedido'], 429);
        }

        // 4. Validar estrutura do payload
        $data = $request->json()->all();
        if (empty($data['data']['key']['remoteJid']) && empty($data['data']['key']['senderPn'])) {
            Log::warning('[SECURITY] JID ausente no payload');
            return response()->json(['error' => 'JID inválido'], 400);
        }

        // 5. Validar JID format
        $jid = $data['data']['key']['remoteJid'] ?? $data['data']['key']['senderPn'] ?? null;
        if (!InputValidationService::validateAndNormalizeJid($jid)) {
            Log::warning('[SECURITY] JID inválido detectado', ['jid' => $jid]);
            return response()->json(['error' => 'JID inválido'], 400);
        }

        // 6. Validar tamanho da mensagem
        $messageText = $data['data']['message']['conversation'] ?? null;
        if ($messageText && strlen($messageText) > 4096) {
            Log::warning('[SECURITY] Mensagem muito longa', ['length' => strlen($messageText)]);
            return response()->json(['error' => 'Mensagem muito longa'], 400);
        }

        // 7. Detecção de SQL injection em mensagem
        if ($messageText && self::detectSqlInjection($messageText)) {
            Log::warning('[SECURITY] SQL injection detectada', ['message' => substr($messageText, 0, 100)]);
            return response()->json(['error' => 'Padrão suspeito detectado'], 400);
        }

        // 8. Adicionar headers de segurança à resposta
        $response = $next($request);
        
        $response->header('X-Request-ID', ObservabilityService::getTraceId());
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');

        return $response;
    }

    /**
     * Detectar padrões comuns de SQL injection
     */
    private static function detectSqlInjection(string $text): bool
    {
        $sqlPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdrop\b.*\b(table|database)\b)/i',
            '/(--|#|\/\*|\*\/)/',
            '/(\bexec\b|\bexecute\b)/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}

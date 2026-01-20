<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * InputValidationService - Validações robustas e segurança
 * 
 * Responsabilidades:
 * - Sanitizar inputs WhatsApp
 * - Validar formato JID
 * - Rate limiting por usuário/IP
 * - Detecção de abuso
 * - Validação de números telefone
 */
class InputValidationService
{
    /**
     * Validar e sanitizar JID WhatsApp
     */
    public static function validateAndNormalizeJid(string $jid): ?string
    {
        if (empty($jid)) {
            Log::warning("[VALIDATION] JID vazio recebido");
            return null;
        }

        // Validar formato básico
        if (!preg_match('/^[\d@g\.]+$/', $jid)) {
            Log::warning("[VALIDATION] JID com caracteres inválidos", ['jid' => $jid]);
            return null;
        }

        // Normalizar: remover espaços, converter para minúsculas se necessário
        $jid = trim($jid);
        
        // Validar comprimento mínimo
        if (strlen($jid) < 15) { // Ex: 5511999999999@s.whatsapp.net
            Log::warning("[VALIDATION] JID muito curto", ['jid' => $jid, 'length' => strlen($jid)]);
            return null;
        }

        return $jid;
    }

    /**
     * Validar número de telefone brasileiro
     */
    public static function validateBrazilianPhone(string $numero): bool
    {
        // Remover caracteres especiais
        $numLimpo = preg_replace('/\D/', '', $numero);
        
        // Deve ter 11 dígitos (DDD + número)
        if (strlen($numLimpo) !== 11) {
            Log::warning("[VALIDATION] Número de telefone inválido", [
                'original' => $numero,
                'limpo' => $numLimpo,
                'length' => strlen($numLimpo)
            ]);
            return false;
        }

        // DDD deve ser 11-99
        $ddd = (int) substr($numLimpo, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            Log::warning("[VALIDATION] DDD inválido", ['ddd' => $ddd]);
            return false;
        }

        // Primeiro dígito do número não pode ser 0
        $firstDigit = (int) substr($numLimpo, 2, 1);
        if ($firstDigit === 0) {
            Log::warning("[VALIDATION] Primeiro dígito do número é 0", ['numero' => $numLimpo]);
            return false;
        }

        return true;
    }

    /**
     * Sanitizar mensagem de texto
     */
    public static function sanitizeMessage(string $mensagem, int $maxLength = 4096): string
    {
        // Remover null bytes
        $mensagem = str_replace("\0", '', $mensagem);
        
        // Limitar comprimento
        $mensagem = substr($mensagem, 0, $maxLength);
        
        // Trim espaços
        $mensagem = trim($mensagem);
        
        // Remover sequências de espaços múltiplos
        $mensagem = preg_replace('/\s+/', ' ', $mensagem);
        
        return $mensagem;
    }

    /**
     * Rate limiting por cliente - máx 30 mensagens por minuto
     */
    public static function checkRateLimit(string $clienteId, int $maxPerMinute = 30): bool
    {
        $key = "ratelimit:cliente:{$clienteId}";
        $limit = RateLimiter::attempt($key, $maxPerMinute, function () {}, 60);
        
        if (!$limit) {
            Log::warning("[RATELIMIT] Cliente excedeu limite", [
                'cliente_id' => $clienteId,
                'limit' => $maxPerMinute
            ]);
            return false;
        }

        return true;
    }

    /**
     * Detectar padrões de abuso (mesmo usuário enviando muitas mensagens idênticas)
     */
    public static function detectAbusivePattern(string $clienteId, string $mensagem): bool
    {
        $cacheKey = "abuse:cliente:{$clienteId}:last_message";
        $lastMessages = cache()->get($cacheKey, []);
        
        // Se a mesma mensagem foi enviada 3+ vezes em 1 minuto
        $count = array_count_values($lastMessages)[$mensagem] ?? 0;
        if ($count >= 3) {
            Log::warning("[ABUSE] Padrão suspeito detectado", [
                'cliente_id' => $clienteId,
                'mensagem' => substr($mensagem, 0, 50),
                'repeticoes' => $count
            ]);
            return true;
        }

        // Adicionar mensagem atual ao histórico
        $lastMessages[] = $mensagem;
        array_splice($lastMessages, 0, -5); // Manter apenas últimas 5
        cache()->put($cacheKey, $lastMessages, now()->addMinute());

        return false;
    }

    /**
     * Validar nome do cliente
     */
    public static function validateClientName(string $nome): bool
    {
        $nome = trim($nome);
        
        // Comprimento mínimo e máximo
        if (strlen($nome) < 2 || strlen($nome) > 100) {
            return false;
        }

        // Apenas letras, números, espaços e alguns caracteres comuns em nomes
        if (!preg_match('/^[\p{L}\p{N}\s\-\'\.]+$/u', $nome)) {
            return false;
        }

        return true;
    }
}

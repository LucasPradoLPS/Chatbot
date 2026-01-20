<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * CacheOptimizationService - Melhora performance com caching inteligente
 * 
 * Estratégias:
 * - Cache de Assistants por 24h (raramente muda)
 * - Cache de Threads por usuário (TTL: 7 dias)
 * - Cache de respostas frequentes (TTL: 1h)
 * - Memoization de cálculos custosos
 */
class CacheOptimizationService
{
    // TTLs (em segundos)
    private const ASSISTANT_CACHE_TTL = 86400; // 24h
    private const THREAD_CACHE_TTL = 604800; // 7 dias
    private const RESPONSE_CACHE_TTL = 3600; // 1h
    private const CONFIG_CACHE_TTL = 3600; // 1h

    /**
     * Obter ou criar assistant com cache
     */
    public static function getAssistantCached(string $assistantId): ?array
    {
        $cacheKey = "assistant:{$assistantId}";
        
        return Cache::remember($cacheKey, self::ASSISTANT_CACHE_TTL, function () use ($assistantId) {
            Log::debug("[CACHE] Cache miss para assistant", ['assistant_id' => $assistantId]);
            
            $apiKey = config('services.openai.key');
            $response = Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->get("https://api.openai.com/v1/assistants/{$assistantId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::warning("[CACHE] Erro ao buscar assistant", [
                'assistant_id' => $assistantId,
                'status' => $response->status()
            ]);
            return null;
        });
    }

    /**
     * Obter ou criar thread com cache por cliente
     */
    public static function getThreadCached(string $clienteId, string $assistantId): ?string
    {
        $cacheKey = "thread:client:{$clienteId}:assistant:{$assistantId}";
        
        return Cache::remember($cacheKey, self::THREAD_CACHE_TTL, function () use ($clienteId, $assistantId) {
            Log::debug("[CACHE] Cache miss para thread", ['client_id' => $clienteId]);
            
            $apiKey = config('services.openai.key');
            $response = Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->post('https://api.openai.com/v1/threads');
            
            if ($response->successful()) {
                $threadId = $response->json('id');
                Log::info("[CACHE] Nova thread criada e cacheada", ['thread_id' => $threadId]);
                return $threadId;
            }
            
            return null;
        });
    }

    /**
     * Cache de respostas para padrões comuns
     */
    public static function getCachedResponse(string $clienteId, string $mensagem): ?string
    {
        $hash = md5("response:{$clienteId}:{$mensagem}");
        return Cache::get($hash);
    }

    /**
     * Armazenar resposta em cache
     */
    public static function setCachedResponse(string $clienteId, string $mensagem, string $resposta): void
    {
        $hash = md5("response:{$clienteId}:{$mensagem}");
        Cache::put($hash, $resposta, self::RESPONSE_CACHE_TTL);
    }

    /**
     * Invalidar cache de cliente (quando sai da conversa ou reinicia)
     */
    public static function invalidateClientCache(string $clienteId): void
    {
        Cache::forget("thread:client:{$clienteId}");
        Log::info("[CACHE] Cache do cliente invalidado", ['client_id' => $clienteId]);
    }

    /**
     * Pré-cache de configurações frequentes
     */
    public static function preCacheConfigs(): void
    {
        Cache::remember('config:assistants:all', self::CONFIG_CACHE_TTL, function () {
            return \App\Models\AgenteGerado::all()->toArray();
        });
    }
}

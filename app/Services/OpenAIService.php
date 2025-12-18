<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    /**
     * Create an Assistant in OpenAI (Assistants v2) and return its ID.
     *
     * @param string $name
     * @param string $instructions
     * @param string|null $model Default model for assistants
     * @return string Assistant ID (starts with asst_)
     * @throws \RuntimeException When API key missing or request fails
     */
    public function createAssistant(string $name, string $instructions, ?string $model = null): string
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_KEY não configurada em .env');
        }

        $model = $model ?: 'gpt-4o-mini';

        $response = Http::withToken($apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->post('https://api.openai.com/v1/assistants', [
                'model' => $model,
                'name' => $name,
                'instructions' => $instructions,
            ]);

        if ($response->failed()) {
            Log::error('Falha ao criar Assistant na OpenAI', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Erro ao criar Assistant na OpenAI');
        }

        $assistantId = $response['id'] ?? null;
        if (!$assistantId) {
            throw new \RuntimeException('Resposta inválida da OpenAI ao criar Assistant');
        }

        return $assistantId;
    }
}

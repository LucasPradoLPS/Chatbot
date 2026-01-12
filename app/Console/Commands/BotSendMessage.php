<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotSendMessage extends Command
{
    protected $signature = 'bot:send {numero} {mensagem} {--instancia=N8n}';
    protected $description = 'Envia uma mensagem de teste via Evolution API para validar envio';

    public function handle()
    {
        $numeroArg = $this->argument('numero');
        $mensagem = $this->argument('mensagem');
        $instancia = $this->option('instancia');

        // Normaliza número (somente dígitos) e monta JID
        $numero = preg_replace('/\D/', '', $numeroArg);
        if (!$numero) {
            $this->error('Número inválido. Informe algo como +5511999999000.');
            return 1;
        }
        $jid = $numero . '@s.whatsapp.net';

        $url = rtrim(config('services.evolution.url'), '/') . "/message/sendText/{$instancia}";
        $apiKey = config('services.evolution.key');
        if (!$apiKey || !$url) {
            $this->error('EVOLUTION_URL ou EVOLUTION_KEY não configurados. Verifique seu .env.');
            return 1;
        }

        $this->info("➡️  Enviando mensagem para {$numero} (jid: {$jid}) via {$url}");

        $payload = [
            'number' => $numero,
            'text' => $mensagem,
            // alguns ambientes requerem explicitamente o jid; incluímos ambos
            'jid' => $jid,
        ];

        try {
            $resp = Http::withHeaders(['apikey' => $apiKey])->post($url, $payload);
            $status = $resp->status();
            $body = (string) $resp->body();

            if ($resp->successful()) {
                $this->info("✅ Sucesso ({$status})");
                $this->line($body);
            } else {
                $this->warn("⚠️ Falha ({$status})");
                $this->line($body);
                // dica: alguns ambientes pedem somente 'number' sem 'jid' ou vice-versa
                $this->line('Dica: verifique se o número está registrado/validado na Evolution.');
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar via Evolution', ['error' => $e->getMessage()]);
            $this->error('Erro ao enviar: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

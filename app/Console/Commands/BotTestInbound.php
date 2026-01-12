<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessWhatsappMessage;

class BotTestInbound extends Command
{
    protected $signature = 'bot:test-inbound {numero?} {--mensagem=Oi! Quero comprar um apartamento.} {--grupo : Simula mensagem vinda de grupo (@g.us) e deve ser ignorada}';
    protected $description = 'Simula uma mensagem inbound do WhatsApp para testar o bot';

    public function handle()
    {
        $numero = $this->argument('numero') ?? '+5511999999008';
        // Monta JID padrão do WhatsApp ou de grupo
        if ($this->option('grupo')) {
            // JID de grupo: qualquer coisa que termine com @g.us será detectado
            $base = preg_replace('/\D/', '', $numero);
            $jid = $base . '-12345@g.us';
        } else {
            $jid = preg_replace('/\D/', '', $numero) . '@s.whatsapp.net';
        }

        $payload = [
            'instance' => 'N8n',
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'remoteJid' => $jid,
                    'senderPn' => preg_replace('/\D/', '', $numero),
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => $this->option('mensagem'),
                ],
                'source' => 'simulate',
            ],
        ];

        $this->info('📨 Simulando inbound para: ' . $numero . ' (JID: ' . $jid . ')' . ($this->option('grupo') ? ' [GRUPO]' : ''));
        (new ProcessWhatsappMessage($payload))->handle();
        $this->info('✅ Processamento concluído. Verifique logs em storage/logs/laravel.log');
        return 0;
    }
}

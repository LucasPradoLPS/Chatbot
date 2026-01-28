<?php
/**
 * Script para enviar mensagem ao bot via webhook
 * Uso: php enviar_msg.php "Sua mensagem aqui"
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mensagem = $argv[1] ?? 'Olá';
$numero = $argv[2] ?? '553199380844';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  📨 ENVIANDO MENSAGEM AO BOT                            ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "📝 Mensagem: $mensagem\n";
echo "📱 Número: $numero\n\n";

$payload = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => str_replace(['@s.whatsapp.net', '@sid'], '', $numero) . '@s.whatsapp.net',
            'senderPn' => $numero,
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'conversation' => $mensagem
        ]
    ]
];

try {
    echo "🔄 Enviando para o webhook...\n";
    
    $urls = [
        'http://localhost:8000/api/webhook/whatsapp',
        'http://127.0.0.1:8000/api/webhook/whatsapp'
    ];
    
    $response = null;
    foreach ($urls as $url) {
        try {
            $response = Http::timeout(5)->post($url, $payload);
            echo "✅ Conectado em: $url\n\n";
            break;
        } catch (Exception $e) {
            continue;
        }
    }
    
    if (!$response) {
        throw new Exception('Servidor não respondeu em nenhuma URL');
    }

    echo "✅ SUCESSO!\n";
    echo "Status: {$response->status()}\n\n";
    
    echo "🔍 Verifique os logs:\n";
    echo "   Get-Content storage\\logs\\laravel.log -Tail 30\n\n";
    
    echo "📊 Esperado nos logs:\n";
    echo "   [ENTRADA] Mensagem recebida de...\n";
    echo "   [MENU] ou [IA] Respondendo...\n";
    echo "   [MENU] Resposta enviada com sucesso\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
    echo "💡 Solução:\n";
    echo "1. Inicie o servidor em outro terminal:\n";
    echo "   cd c:\\Users\\lucas\\Downloads\\Chatbot-laravel\n";
    echo "   php artisan serve --host=0.0.0.0 --port=8000\n";
    echo "2. Depois tente enviar novamente\n";
}

echo "\n";

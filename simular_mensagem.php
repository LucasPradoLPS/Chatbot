<?php
/**
 * Script para simular chegada de mensagem no webhook
 * Use este script para testar se o bot responde
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  🧪 SIMULANDO MENSAGEM NO WEBHOOK                      ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// URL do webhook
$webhookUrl = 'http://127.0.0.1:8000/api/webhook/whatsapp';

// Payload simulando mensagem real do WhatsApp
$payload = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => '5511987654321@s.whatsapp.net',
            'senderPn' => '5511987654321',
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'conversation' => 'Olá, quero informações sobre apartamentos disponíveis'
        ]
    ]
];

echo "📨 Enviando mensagem simulada...\n\n";
echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

try {
    $response = Http::timeout(30)
        ->post($webhookUrl, $payload);

    echo "✅ RESPOSTA DO SERVIDOR\n";
    echo "────────────────────────\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
    
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    echo "❌ ERRO DE CONEXÃO\n";
    echo "────────────────────────\n";
    echo "Servidor não respondeu em $webhookUrl\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    echo "📝 Soluções:\n";
    echo "1. Iniciar servidor: php artisan serve --host=127.0.0.1 --port=8000\n";
    echo "2. Verificar porta 8000\n";
} catch (Exception $e) {
    echo "❌ ERRO GERAL\n";
    echo "────────────────────────\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
}

echo "\n\n📝 VERIFICAR LOGS:\n";
echo "Get-Content storage\\logs\\laravel.log -Wait -Tail 30\n";

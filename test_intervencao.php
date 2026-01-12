<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessWhatsappMessage;
use App\Models\InstanciaWhatsapp;
use Illuminate\Support\Facades\Log;

$instancia = InstanciaWhatsapp::first();

echo "=== TESTE 3: Intervenção Humana (fromMe=true, source=web) ===\n\n";

// Operador envia mensagem (fromMe=true, source='web')
$payload = [
    'instance' => $instancia->instance_name,
    'data' => [
        'key' => [
            'remoteJid' => '5531999999003@s.whatsapp.net',
            'senderPn' => '5531999999003',
            'fromMe' => true,
            'id' => 'TEST_FROMME_' . uniqid(),
        ],
        'message' => [
            'conversation' => 'Oi, sou o vendedor! Como posso ajudar?',
        ],
        'source' => 'web',
    ],
];

echo "📨 Simulando mensagem do operador (fromMe=web)...\n";
(new ProcessWhatsappMessage($payload))->handle();
echo "✅ Teste 3 executado - verificar logs para [INTERVENCAO]\n\n";

echo "=== TESTE 4: Cliente responde após intervenção ===\n\n";

// Cliente responde
$payload2 = [
    'instance' => $instancia->instance_name,
    'data' => [
        'key' => [
            'remoteJid' => '5531999999003@s.whatsapp.net',
            'senderPn' => '5531999999003',
            'fromMe' => false,
            'id' => 'TEST_RESPONSE_' . uniqid(),
        ],
        'message' => [
            'conversation' => 'Sim, tenho interesse no apartamento!',
        ],
        'source' => 'android',
    ],
];

echo "📨 Simulando resposta do cliente (fromMe=false)...\n";
(new ProcessWhatsappMessage($payload2))->handle();
echo "✅ Teste 4 executado - bot deve estar bloqueado [BLOQUEADO] IA pausada...\n";

<?php

/**
 * Script para testar envio de imagem ao webhook
 * Simula o que o WhatsApp/Evolution enviaria
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  📸 TESTE DE ENVIO DE IMAGEM AO CHATBOT              ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// URL do webhook local
$webhookUrl = 'http://127.0.0.1:8000/api/webhook/whatsapp';

// Número de teste
$numeroTeste = '5511987654321';

// Dados da imagem (simulando Evolution API)
$payload = [
    'instance' => 'seu_numero_whatsapp',
    'data' => [
        'key' => [
            'remoteJid' => $numeroTeste . '@s.whatsapp.net',
            'senderPn' => '55' . preg_replace('/\D/', '', $numeroTeste),
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'imageMessage' => [
                'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg/640px-Fronalpstock_full.jpg',
                'mimetype' => 'image/jpeg',
                'caption' => 'Foto de teste - imóvel',
                'mediaKey' => 'ABC123DEF456GHI789'
            ]
        ]
    ]
];

echo "📸 Imagem a ser testada:\n";
echo "─────────────────────────────────────\n";
echo "URL: https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg\n";
echo "Tipo: Paisagem natural (montanha)\n";
echo "MIME: image/jpeg\n\n";

echo "🚀 Enviando para webhook...\n";
echo "   URL: $webhookUrl\n";
echo "   Método: POST\n";
echo "   De: $numeroTeste\n\n";

try {
    $response = Http::timeout(60)
        ->post($webhookUrl, $payload);

    echo "✅ Requisição enviada!\n";
    echo "   Status HTTP: " . $response->status() . "\n";
    
    if ($response->successful()) {
        echo "   ✓ Sucesso! Resposta recebida\n\n";
    } else {
        echo "   ⚠️  Status não é 2xx\n\n";
    }
    
    echo "📋 Resposta do servidor:\n";
    echo "─────────────────────────────────────\n";
    echo substr($response->body(), 0, 300) . "\n\n";

} catch (Exception $e) {
    echo "❌ Erro ao enviar:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

echo "📊 PRÓXIMOS PASSOS:\n";
echo "─────────────────────────────────────\n";
echo "1. Verifique os logs:\n";
echo "   tail -f storage/logs/laravel.log\n\n";
echo "2. Procure por:\n";
echo "   - '[VALIDACAO]' (validação da imagem)\n";
echo "   - '[MIDIA PROCESSADA]' (sucesso)\n";
echo "   - 'OpenAI Vision' (análise da imagem)\n\n";
echo "3. Verifique armazenamento:\n";
echo "   ls -la storage/app/public/whatsapp_media/images/\n\n";

echo "💡 DICA:\n";
echo "─────────────────────────────────────\n";
echo "Se tudo funcionar, a resposta será enviada de volta\n";
echo "ao Evolution API, que entregaria ao WhatsApp!\n\n";

echo "🎯 Para testar TUDO junto:\n";
echo "   php testar_imagem_completo.php\n\n";

<?php

/**
 * Teste Prático - Webhook Simples com Imagem Fake
 * Simula um webhook da Evolution API enviando uma imagem
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  📸 TESTE: Webhook com Imagem Local                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Cria uma imagem PNG simples
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 0, 100, 200);
imagefilledrectangle($image, 0, 0, $width, $height, $white);
imagefilledrectangle($image, 50, 50, 150, 150, $blue);

ob_start();
imagepng($image);
$imageBuffer = ob_get_clean();
imagedestroy($image);

// Salva na pasta pública para servir
Storage::disk('public')->put('temp_test_image.png', $imageBuffer);
$localPath = '/storage/temp_test_image.png';
$fullUrl = 'http://127.0.0.1:8000' . $localPath;

// Monta payload do webhook conforme a Evolution API envia
$webhookPayload = [
    'instance' => 'seu_numero_whatsapp',
    'data' => [
        'key' => [
            'remoteJid' => '5511987654321@s.whatsapp.net',
            'senderPn' => '555511987654321',
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'imageMessage' => [
                'url' => $fullUrl,
                'mimetype' => 'image/png',
                'caption' => 'Imagem de Teste Local'
            ]
        ]
    ]
];

echo "📋 Enviando para webhook...\n";
echo "   URL: http://127.0.0.1:8000/api/webhook/whatsapp\n";
echo "   Imagem: $fullUrl\n\n";

try {
    $response = Http::post('http://127.0.0.1:8000/api/webhook/whatsapp', $webhookPayload);
    
    echo "✅ Resposta recebida!\n";
    echo "   Status: " . $response->status() . "\n";
    echo "   Body: " . $response->body() . "\n\n";
    
    // Aguarda processamento
    sleep(3);
    
    // Verifica se a imagem foi armazenada
    $files = Storage::disk('public')->files('whatsapp_media/images');
    if (count($files) > 0) {
        echo "✅ Imagens processadas encontradas:\n";
        foreach ($files as $file) {
            echo "   ├─ $file\n";
        }
    } else {
        echo "⚠️  Nenhuma imagem processada encontrada\n";
    }
    
    // Verifica logs
    echo "\n📝 Últimos logs:\n";
    $logs = shell_exec('Get-Content storage/logs/laravel.log -Tail 10 2>/dev/null || tail -n 10 storage/logs/laravel.log');
    echo $logs;
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n";

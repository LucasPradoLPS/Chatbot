<?php

/**
 * Script de teste do MediaProcessor via webhook simulado
 * Simula requisições WhatsApp enviando diferentes tipos de mídia
 * 
 * Uso:
 *   php test_media_webhook.php image
 *   php test_media_webhook.php pdf
 *   php test_media_webhook.php document
 *   php test_media_webhook.php all
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$tipo = $argv[1] ?? 'all';

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TESTE DE WEBHOOK - SIMULAÇÃO DE WHATSAPP          ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// URL do webhook local
$webhookUrl = 'http://127.0.0.1:8000/api/webhook/whatsapp';

// Número de teste
$numeroTeste = '5511987654321';

// ===== TESTE 1: IMAGEM =====
if (in_array($tipo, ['image', 'all'])) {
    echo "\n📷 TESTE 1: SIMULANDO ENVIO DE IMAGEM\n";
    echo "─────────────────────────────────────\n\n";

    $payloadImagem = [
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
                    'caption' => 'Foto do imóvel para análise',
                    'mediaKey' => 'ABC123DEF456'
                ]
            ]
        ]
    ];

    echo "🔄 Enviando payload (imagem)...\n\n";
    echo json_encode($payloadImagem, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    try {
        $response = Http::timeout(60)
            ->post($webhookUrl, $payloadImagem);

        echo "✅ Resposta HTTP " . $response->status() . "\n";
        if ($response->successful()) {
            echo "Body: " . substr($response->body(), 0, 200) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro na requisição: " . $e->getMessage() . "\n";
    }
}

// ===== TESTE 2: PDF =====
if (in_array($tipo, ['pdf', 'all'])) {
    echo "\n\n📄 TESTE 2: SIMULANDO ENVIO DE PDF\n";
    echo "─────────────────────────────────────\n\n";

    $payloadPDF = [
        'instance' => 'seu_numero_whatsapp',
        'data' => [
            'key' => [
                'remoteJid' => $numeroTeste . '@s.whatsapp.net',
                'senderPn' => '55' . preg_replace('/\D/', '', $numeroTeste),
                'id' => 'msg_' . uniqid(),
                'fromMe' => false
            ],
            'message' => [
                'documentMessage' => [
                    'url' => 'https://www.w3.org/TR/PNG/iso_8859-1.txt',
                    'mimetype' => 'application/pdf',
                    'filename' => 'contrato.pdf',
                    'caption' => 'Contrato de venda',
                    'mediaKey' => 'GHI789JKL012'
                ]
            ]
        ]
    ];

    echo "🔄 Enviando payload (PDF)...\n\n";
    echo json_encode($payloadPDF, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    try {
        $response = Http::timeout(60)
            ->post($webhookUrl, $payloadPDF);

        echo "✅ Resposta HTTP " . $response->status() . "\n";
        if ($response->successful()) {
            echo "Body: " . substr($response->body(), 0, 200) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro na requisição: " . $e->getMessage() . "\n";
    }
}

// ===== TESTE 3: DOCUMENTO (CSV) =====
if (in_array($tipo, ['document', 'all'])) {
    echo "\n\n📊 TESTE 3: SIMULANDO ENVIO DE DOCUMENTO (CSV)\n";
    echo "─────────────────────────────────────\n\n";

    $payloadCSV = [
        'instance' => 'seu_numero_whatsapp',
        'data' => [
            'key' => [
                'remoteJid' => $numeroTeste . '@s.whatsapp.net',
                'senderPn' => '55' . preg_replace('/\D/', '', $numeroTeste),
                'id' => 'msg_' . uniqid(),
                'fromMe' => false
            ],
            'message' => [
                'documentMessage' => [
                    'url' => 'https://people.sc.fsu.edu/~jburkardt/data/csv/airtravel.csv',
                    'mimetype' => 'text/csv',
                    'filename' => 'imoveis.csv',
                    'caption' => 'Lista de imóveis',
                    'mediaKey' => 'MNO345PQR678'
                ]
            ]
        ]
    ];

    echo "🔄 Enviando payload (CSV)...\n\n";
    echo json_encode($payloadCSV, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    try {
        $response = Http::timeout(60)
            ->post($webhookUrl, $payloadCSV);

        echo "✅ Resposta HTTP " . $response->status() . "\n";
        if ($response->successful()) {
            echo "Body: " . substr($response->body(), 0, 200) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro na requisição: " . $e->getMessage() . "\n";
    }
}

// ===== RESUMO =====
echo "\n\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  TESTES DE WEBHOOK CONCLUÍDOS                          ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "📋 VERIFICAÇÃO DE LOGS:\n";
echo "─────────────────────────────────────\n";
echo "1. Verificar processamento:\n";
echo "   tail -f storage/logs/laravel.log\n\n";

echo "2. Procurar por:\n";
echo "   - '[VALIDACAO] Resposta é válida'\n";
echo "   - '[MIDIA PROCESSADA]'\n";
echo "   - 'Evolution API response'\n\n";

echo "3. Verificar armazenamento:\n";
echo "   ls -la storage/app/public/whatsapp_media/\n\n";

echo "📊 EXPECTED BEHAVIOR:\n";
echo "─────────────────────────────────────\n";
echo "✓ Imagem: Analisada com OpenAI Vision\n";
echo "✓ PDF: Texto extraído\n";
echo "✓ CSV: Conteúdo exibido\n";
echo "✓ Resposta enviada ao bot\n";
echo "✓ Arquivos armazenados com UUID\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "─────────────────────────────────────\n";
echo "1. Servidor deve estar rodando:\n";
echo "   php artisan serve --host=127.0.0.1 --port=8000\n\n";

echo "2. Database deve estar acessível\n";
echo "3. OPENAI_KEY deve estar em .env\n";
echo "4. Evolution API configurada em .env\n\n";

echo "🔗 Links úteis:\n";
echo "─────────────────────────────────────\n";
echo "Documentação: MEDIA_PROCESSOR_README.md\n";
echo "Guia Completo: MEDIA_PROCESSOR_GUIA.md\n";
echo "Fluxos: MEDIA_PROCESSOR_FLUXO.md\n\n";

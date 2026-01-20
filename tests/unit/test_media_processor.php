<?php

/**
 * Script de Teste do MediaProcessor
 * Testa processamento de diferentes tipos de mídia
 * 
 * Uso:
 *   php test_media_processor.php [tipo_teste]
 *   php test_media_processor.php image    # Testa imagem
 *   php test_media_processor.php pdf      # Testa PDF
 *   php test_media_processor.php all      # Testa todos
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Services\MediaProcessor;

$tipoTeste = $argv[1] ?? 'all';

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  🤖 TESTE DO MEDIA PROCESSOR - AGENTE DE MÍDIA         ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Configurações de teste
$testImages = [
    'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg/640px-Fronalpstock_full.jpg',
    'https://via.placeholder.com/200x200'
];

$testPDFs = [
    'https://www.w3.org/TR/PNG/iso_8859-1.txt', // Simula PDF como TXT
];

$mediaProcessor = new MediaProcessor();

// ===== TESTE 1: IMAGEM =====
if (in_array($tipoTeste, ['image', 'all'])) {
    echo "\n📷 TESTE 1: PROCESSAMENTO DE IMAGEM\n";
    echo "─────────────────────────────────────\n\n";

    $msgDataImagem = [
        'imageMessage' => [
            'url' => $testImages[0],
            'mimetype' => 'image/jpeg',
            'caption' => 'Foto de montanha para análise'
        ]
    ];

    echo "🔄 Processando imagem...\n";
    $resultado = $mediaProcessor->processar($msgDataImagem);

    if ($resultado['success']) {
        echo "✅ SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "📝 Conteúdo extraído:\n";
        echo "─────────────────────────────────────\n";
        echo substr($resultado['conteudo_extraido'], 0, 500) . "...\n";
    } else {
        echo "❌ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== TESTE 2: DOCUMENTO/PDF =====
if (in_array($tipoTeste, ['pdf', 'document', 'all'])) {
    echo "\n\n📄 TESTE 2: PROCESSAMENTO DE DOCUMENTO\n";
    echo "─────────────────────────────────────\n\n";

    $msgDataDoc = [
        'documentMessage' => [
            'url' => 'https://people.sc.fsu.edu/~jburkardt/data/csv/airtravel.csv',
            'mimetype' => 'text/csv',
            'filename' => 'dados_teste.csv'
        ]
    ];

    echo "🔄 Processando documento...\n";
    $resultado = $mediaProcessor->processar($msgDataDoc);

    if ($resultado['success']) {
        echo "✅ SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Nome original: " . $resultado['metadados']['nome_original'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "📝 Conteúdo extraído:\n";
        echo "─────────────────────────────────────\n";
        echo substr($resultado['conteudo_extraido'], 0, 500) . "...\n";
    } else {
        echo "❌ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== TESTE 3: ÁUDIO =====
if (in_array($tipoTeste, ['audio', 'all'])) {
    echo "\n\n🎙️  TESTE 3: PROCESSAMENTO DE ÁUDIO\n";
    echo "─────────────────────────────────────\n\n";

    $msgDataAudio = [
        'audioMessage' => [
            'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'mimetype' => 'audio/mpeg'
        ]
    ];

    echo "🔄 Processando áudio...\n";
    $resultado = $mediaProcessor->processar($msgDataAudio);

    if ($resultado['success']) {
        echo "✅ SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "📝 Informação:\n";
        echo "─────────────────────────────────────\n";
        echo $resultado['conteudo_extraido'] . "\n";
    } else {
        echo "❌ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== RESUMO =====
echo "\n\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  TESTES CONCLUÍDOS                                     ║\n";
echo "║  📂 Arquivos armazenados em: storage/app/public/       ║\n";
echo "║     whatsapp_media/                                    ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "📊 RESUMO DE FUNCIONALIDADES:\n";
echo "─────────────────────────────────────\n";
echo "✓ Processamento de imagens com OpenAI Vision\n";
echo "✓ Extração de texto de documentos\n";
echo "✓ Processamento de arquivos CSV\n";
echo "✓ Suporte a múltiplos formatos\n";
echo "✓ Armazenamento seguro com UUID\n";
echo "✓ Logging estruturado\n";
echo "✓ Tratamento de erros robusto\n\n";

echo "🚀 PRÓXIMOS PASSOS:\n";
echo "─────────────────────────────────────\n";
echo "1. Instalar bibliotecas opcionais:\n";
echo "   composer require spatie/pdf-to-text\n";
echo "   composer require phpoffice/phpword\n\n";
echo "2. Configurar em .env:\n";
echo "   OPENAI_KEY=sk-proj-xxxxx\n\n";
echo "3. Testar com WhatsApp real:\n";
echo "   Enviar imagem/PDF ao bot\n\n";

echo "📚 Documentação completa em: MEDIA_PROCESSOR_GUIA.md\n\n";

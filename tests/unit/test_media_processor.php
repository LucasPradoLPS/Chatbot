<?php

/**
 * Script de Teste do MediaProcessor
 * Testa processamento de diferentes tipos de mÃ­dia
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
echo "â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—\n";
echo "â•‘  ðŸ¤– TESTE DO MEDIA PROCESSOR - AGENTE DE MÃDIA         â•‘\n";
echo "â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n\n";

// ConfiguraÃ§Ãµes de teste
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
    echo "\nðŸ“· TESTE 1: PROCESSAMENTO DE IMAGEM\n";
    echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n\n";

    $msgDataImagem = [
        'imageMessage' => [
            'url' => $testImages[0],
            'mimetype' => 'image/jpeg',
            'caption' => 'Foto de montanha para anÃ¡lise'
        ]
    ];

    echo "ðŸ”„ Processando imagem...\n";
    $resultado = $mediaProcessor->processar($msgDataImagem);

    if ($resultado['success']) {
        echo "âœ… SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "ðŸ“ ConteÃºdo extraÃ­do:\n";
        echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
        echo substr($resultado['conteudo_extraido'], 0, 500) . "...\n";
    } else {
        echo "âŒ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== TESTE 2: DOCUMENTO/PDF =====
if (in_array($tipoTeste, ['pdf', 'document', 'all'])) {
    echo "\n\nðŸ“„ TESTE 2: PROCESSAMENTO DE DOCUMENTO\n";
    echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n\n";

    $msgDataDoc = [
        'documentMessage' => [
            'url' => 'https://people.sc.fsu.edu/~jburkardt/data/csv/airtravel.csv',
            'mimetype' => 'text/csv',
            'filename' => 'dados_teste.csv'
        ]
    ];

    echo "ðŸ”„ Processando documento...\n";
    $resultado = $mediaProcessor->processar($msgDataDoc);

    if ($resultado['success']) {
        echo "âœ… SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Nome original: " . $resultado['metadados']['nome_original'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "ðŸ“ ConteÃºdo extraÃ­do:\n";
        echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
        echo substr($resultado['conteudo_extraido'], 0, 500) . "...\n";
    } else {
        echo "âŒ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== TESTE 3: ÃUDIO =====
if (in_array($tipoTeste, ['audio', 'all'])) {
    echo "\n\nðŸŽ™ï¸  TESTE 3: PROCESSAMENTO DE ÃUDIO\n";
    echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n\n";

    $msgDataAudio = [
        'audioMessage' => [
            'url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'mimetype' => 'audio/mpeg'
        ]
    ];

    echo "ðŸ”„ Processando Ã¡udio...\n";
    $resultado = $mediaProcessor->processar($msgDataAudio);

    if ($resultado['success']) {
        echo "âœ… SUCESSO!\n\n";
        echo "Tipo: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo: " . $resultado['arquivo_local'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n\n";
        echo "ðŸ“ InformaÃ§Ã£o:\n";
        echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
        echo $resultado['conteudo_extraido'] . "\n";
    } else {
        echo "âŒ ERRO: " . $resultado['erro'] . "\n";
    }
}

// ===== RESUMO =====
echo "\n\n";
echo "â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—\n";
echo "â•‘  TESTES CONCLUÃDOS                                     â•‘\n";
echo "â•‘  ðŸ“‚ Arquivos armazenados em: storage/app/public/       â•‘\n";
echo "â•‘     whatsapp_media/                                    â•‘\n";
echo "â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n\n";

echo "ðŸ“Š RESUMO DE FUNCIONALIDADES:\n";
echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
echo "âœ“ Processamento de imagens com OpenAI Vision\n";
echo "âœ“ ExtraÃ§Ã£o de texto de documentos\n";
echo "âœ“ Processamento de arquivos CSV\n";
echo "âœ“ Suporte a mÃºltiplos formatos\n";
echo "âœ“ Armazenamento seguro com UUID\n";
echo "âœ“ Logging estruturado\n";
echo "âœ“ Tratamento de erros robusto\n\n";

echo "ðŸš€ PRÃ“XIMOS PASSOS:\n";
echo "â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n";
echo "1. Instalar bibliotecas opcionais:\n";
echo "   composer require spatie/pdf-to-text\n";
echo "   composer require phpoffice/phpword\n\n";
echo "2. Configurar em .env:\n";
echo "   OPENAI_KEY=YOUR_OPENAI_KEY";
echo "3. Testar com WhatsApp real:\n";
echo "   Enviar imagem/PDF ao bot\n\n";

echo "ðŸ“š DocumentaÃ§Ã£o completa em: MEDIA_PROCESSOR_GUIA.md\n\n";


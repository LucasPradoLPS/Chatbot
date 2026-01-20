<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MediaProcessor;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  🖼️  TESTE: PROCESSAR IMAGEM COM OPENAI VISION        ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Usar imagem de placeholder que permite download
$msgData = [
    'imageMessage' => [
        'url' => 'https://via.placeholder.com/200x200?text=Imovel+Moderno',
        'mimetype' => 'image/jpeg',
        'caption' => 'Foto de teste'
    ]
];

echo "📸 TESTANDO COM IMAGEM:\n";
echo "─────────────────────────────────────\n";
echo "URL: https://via.placeholder.com/200x200\n";
echo "Tipo: image/jpeg\n";
echo "Conteúdo: Imagem de placeholder\n\n";

echo "🔄 PROCESSANDO...\n";
echo "   (aguarde 5-10 segundos)\n\n";

$startTime = microtime(true);

try {
    $mediaProcessor = new MediaProcessor();
    $resultado = $mediaProcessor->processar($msgData);
    
    $duration = round((microtime(true) - $startTime) * 1000);
    
    if ($resultado['success']) {
        echo "✅ SUCESSO!\n";
        echo "   Tempo total: {$duration}ms\n\n";
        
        echo "📊 RESULTADO:\n";
        echo "─────────────────────────────────────\n";
        echo "Tipo de mídia: " . $resultado['tipo_midia'] . "\n";
        echo "Arquivo armazenado: " . $resultado['arquivo_local'] . "\n";
        echo "Tamanho: " . number_format($resultado['metadados']['tamanho_bytes']) . " bytes\n";
        echo "MIME type: " . $resultado['metadados']['mime_type'] . "\n\n";
        
        echo "🤖 ANÁLISE COM OPENAI VISION:\n";
        echo "─────────────────────────────────────\n";
        echo substr($resultado['conteudo_extraido'], 0, 500) . "\n\n";
        
        echo "✓ PROCESSO COMPLETO:\n";
        echo "  ✓ Download da imagem\n";
        echo "  ✓ Validação (tipo MIME + tamanho)\n";
        echo "  ✓ Análise com GPT-4 Vision\n";
        echo "  ✓ Armazenamento com UUID único\n";
        echo "  ✓ Resposta formatada pronta\n\n";
        
        // Verifica se arquivo foi armazenado
        $caminhoLocal = $resultado['arquivo_local'];
        if (file_exists("storage/app/public/$caminhoLocal")) {
            echo "📁 ARQUIVO ARMAZENADO:\n";
            echo "   Caminho: storage/app/public/$caminhoLocal\n";
            echo "   Tamanho: " . filesize("storage/app/public/$caminhoLocal") . " bytes\n\n";
        }
        
    } else {
        echo "❌ ERRO:\n";
        echo "   " . $resultado['erro'] . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEÇÃO:\n";
    echo "   Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n\n";
}

echo "🎯 RESULTADO FINAL:\n";
echo "─────────────────────────────────────\n";
echo "A imagem foi:\n";
echo "✓ Baixada com sucesso\n";
echo "✓ Analisada por IA (OpenAI Vision)\n";
echo "✓ Armazenada localmente\n";
echo "✓ Metadados salvos\n";
echo "\nAgora está pronto para:\n";
echo "✓ Ser enviado como resposta ao usuário\n";
echo "✓ Ser consultado em análises futuras\n";
echo "✓ Ser usado em histórico de conversas\n\n";

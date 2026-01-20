<?php

/**
 * Teste Direto do MediaProcessor - Processamento de Imagem Local
 * Simula o processamento sem depender de URLs externas
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MediaProcessor;
use Illuminate\Support\Facades\Log;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  🖼️  TESTE: MediaProcessor com Imagem Embutida       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Cria uma imagem PNG simples em memória
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Cores
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 0, 100, 200);
$red = imagecolorallocate($image, 255, 0, 0);
$black = imagecolorallocate($image, 0, 0, 0);

// Preenche fundo branco
imagefilledrectangle($image, 0, 0, $width, $height, $white);

// Desenha um retângulo azul
imagefilledrectangle($image, 50, 50, 150, 150, $blue);

// Desenha um círculo vermelho
imagefilledellipse($image, 100, 100, 50, 50, $red);

// Texto
imagestring($image, 5, 60, 20, 'Imagem Teste', $black);

// Salva em buffer
ob_start();
imagepng($image);
$imageBuffer = ob_get_clean();
imagedestroy($image);

// Simula dados que viriam da API Evolution/WhatsApp
$mockMsgData = [
    'imageMessage' => [
        'url' => 'data:image/png;base64,' . base64_encode($imageBuffer),
        'mimetype' => 'image/png',
        'caption' => 'Teste com imagem gerada'
    ]
];

echo "📊 Dados da Imagem:\n";
echo "   ├─ Tipo: PNG\n";
echo "   ├─ Tamanho: " . strlen($imageBuffer) . " bytes\n";
echo "   └─ MIME: image/png\n\n";

// Processa
echo "🔄 Processando com MediaProcessor...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$mediaProcessor = new MediaProcessor();
$resultado = $mediaProcessor->processar($mockMsgData);

echo "✅ Resultado:\n";
echo "   Sucesso: " . ($resultado['success'] ? 'SIM ✓' : 'NÃO ✗') . "\n";
echo "   Tipo de Mídia: " . $resultado['tipo_midia'] . "\n";

if ($resultado['success']) {
    echo "   Arquivo Local: " . $resultado['arquivo_local'] . "\n";
    echo "\n📝 Conteúdo Extraído:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $resultado['conteudo_extraido'] . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    echo "\n🔍 Metadados:\n";
    foreach ($resultado['metadados'] as $chave => $valor) {
        echo "   ├─ $chave: " . (is_array($valor) ? json_encode($valor) : $valor) . "\n";
    }
} else {
    echo "   ❌ Erro: " . $resultado['erro'] . "\n";
}

echo "\n═════════════════════════════════════════════════════════\n\n";

// Verifica se arquivo foi criado
if ($resultado['success']) {
    $fullPath = storage_path('app/' . $resultado['arquivo_local']);
    if (file_exists($fullPath)) {
        echo "✅ Arquivo armazenado com sucesso!\n";
        echo "   Caminho: $fullPath\n";
        echo "   Tamanho: " . filesize($fullPath) . " bytes\n";
    } else {
        echo "❌ Arquivo não foi criado!\n";
        echo "   Caminho esperado: $fullPath\n";
    }
} else {
    echo "❌ Erro ao processar - verifique logs\n";
}

echo "\n";

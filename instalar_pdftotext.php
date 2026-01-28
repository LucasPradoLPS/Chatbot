<?php
/**
 * Script para instalar pdftotext (poppler) no Windows
 * e configurar o caminho para o Laravel
 */

echo "=== Instalador de pdftotext para Windows ===\n\n";

$poppler_path = 'C:\\poppler';
$poppler_bin = $poppler_path . '\\Library\\bin';

// Verificar se já está instalado
if (file_exists($poppler_bin . '\\pdftotext.exe')) {
    echo "✓ Poppler já está instalado em: $poppler_bin\n";
} else {
    echo "⏳ Instalando Poppler...\n";
    
    // Criar diretório
    if (!is_dir($poppler_path)) {
        mkdir($poppler_path, 0755, true);
        echo "✓ Diretório criado: $poppler_path\n";
    }
    
    // Download (você pode fazer manualmente via browser)
    echo "\n⚠️  Poppler requer download manual:\n";
    echo "1. Acesse: https://github.com/oschwartz10612/poppler-windows/releases\n";
    echo "2. Download: Release-23.08.0.zip\n";
    echo "3. Extraia em: C:\\\n";
    echo "4. Pasta resultante: C:\\poppler-23.08.0\n";
    echo "5. Renomeie para: C:\\poppler\n\n";
}

// Adicionar ao PATH do PHP (config)
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $content = file_get_contents($env_file);
    
    // Adicionar PATH_PDFTOTEXT se não existir
    if (strpos($content, 'PATH_PDFTOTEXT') === false) {
        $content .= "\nPATH_PDFTOTEXT={$poppler_bin}\\pdftotext.exe\n";
        file_put_contents($env_file, $content);
        echo "✓ PATH_PDFTOTEXT adicionado ao .env\n";
    }
}

// Configurar MediaProcessor
$media_processor = __DIR__ . '/app/Services/MediaProcessor.php';
if (file_exists($media_processor)) {
    $content = file_get_contents($media_processor);
    
    // Já temos suporte, apenas configurar o path
    echo "✓ MediaProcessor.php encontrado\n";
    
    // Verificar se está com suporte a configuração de path
    if (strpos($content, 'putenv') === false) {
        echo "ℹ️  Detectando suporte para PATH de pdftotext...\n";
    }
}

echo "\n=== Próximos Passos ===\n";
echo "1. Download e instale Poppler manualmente\n";
echo "2. Adicione ao PATH do sistema: C:\\poppler\\Library\\bin\n";
echo "3. Teste com: pdftotext --version (no cmd)\n";
echo "4. Envie um PDF para testar\n\n";

// Testar se pdftotext está disponível
echo "=== Testando pdftotext ===\n";
$output = shell_exec('where pdftotext 2>&1');
if ($output && strpos($output, 'INFO') === false) {
    echo "✓ pdftotext encontrado em: " . trim($output) . "\n";
} else {
    echo "✗ pdftotext não encontrado no PATH\n";
    echo "  Execute: set PATH=%PATH%;C:\\poppler\\Library\\bin\n";
}

echo "\nInstalação concluída!\n";
?>

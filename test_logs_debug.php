<?php

// Script para testar a listagem de logs com debug

$logsDir = __DIR__ . '/storage/logs';

echo "=== DEBUG - INFORMAÇÕES DO DIRETÓRIO DE LOGS ===\n";
echo "Caminho: $logsDir\n";
echo "Existe: " . (is_dir($logsDir) ? 'SIM' : 'NÃO') . "\n";
echo "Legível: " . (is_readable($logsDir) ? 'SIM' : 'NÃO') . "\n";
echo "Permissões: " . substr(sprintf('%o', fileperms($logsDir)), -4) . "\n";
echo "\n";

if (!is_dir($logsDir)) {
    echo "❌ ERRO: Diretório não existe!\n";
    exit(1);
}

if (!is_readable($logsDir)) {
    echo "❌ ERRO: Diretório não é legível!\n";
    exit(1);
}

echo "=== LISTANDO ARQUIVOS ===\n";
$files = scandir($logsDir);

if ($files === false) {
    echo "❌ ERRO: Não foi possível fazer scandir\n";
    exit(1);
}

$logFiles = [];
foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $filePath = $logsDir . DIRECTORY_SEPARATOR . $file;
    
    if (is_file($filePath)) {
        $size = filesize($filePath);
        $readable = is_readable($filePath);
        $lastModified = date('Y-m-d H:i:s', filemtime($filePath));
        
        echo "\n📄 $file\n";
        echo "   Caminho: $filePath\n";
        echo "   Tamanho: " . formatBytes($size) . " ($size bytes)\n";
        echo "   Legível: " . ($readable ? 'SIM' : 'NÃO') . "\n";
        echo "   Modificado: $lastModified\n";
        
        if ($readable && $size > 0) {
            $lines = file($filePath);
            echo "   Linhas: " . count($lines) . "\n";
            echo "   Últimas 5 linhas:\n";
            $lastLines = array_slice($lines, -5);
            foreach ($lastLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    echo "      > " . mb_substr($line, 0, 100) . "\n";
                }
            }
        } else if ($size === 0) {
            echo "   ⚠️  Arquivo vazio\n";
        }
        
        $logFiles[] = $file;
    }
}

echo "\n=== RESUMO ===\n";
echo "Total de arquivos de log: " . count($logFiles) . "\n";
if (empty($logFiles)) {
    echo "❌ Nenhum arquivo de log encontrado!\n";
} else {
    echo "✓ Arquivos encontrados:\n";
    foreach ($logFiles as $file) {
        echo "   - $file\n";
    }
}

function formatBytes($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

<?php
$files = [
    'app/Jobs/ProcessWhatsappMessage.php',
    'app/Jobs/SendHumanHandoffMessage.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Remove comentários de linha
    $content = preg_replace('~^\s*//.*$~m', '', $content);
    
    // Remove comentários de bloco
    $content = preg_replace('~/\*.*?\*/~s', '', $content);
    
    // Remove linhas vazias múltiplas
    $content = preg_replace('~\n\s*\n+~', "\n", $content);
    
    file_put_contents($file, $content);
    echo "✓ $file\n";
}

echo "Todos os comentários foram removidos!";

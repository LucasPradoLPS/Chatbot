<?php
require 'vendor/autoload.php';

use App\Services\IntentDetector;

// Testes de detecção
$testes = [
    'Olá',
    'Oi',
    'Oi!',
    'Olá!',
    'Oi, tudo bem?',
    'Olá, tudo bem?',
    'ola',
    'oi',
    'OLA',
    'OI',
];

echo "\n========== TESTE DE DETECÇÃO DE SAUDAÇÃO ==========\n\n";

foreach ($testes as $teste) {
    $intent = IntentDetector::detect($teste);
    $status = ($intent === 'saudacao') ? '✅' : '❌';
    echo "$status '$teste' → intent: '$intent'\n";
}

echo "\n====================================================\n\n";

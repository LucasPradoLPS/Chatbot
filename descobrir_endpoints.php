#!/usr/bin/env php
<?php

/**
 * 🔍 DESCOBRIR ENDPOINTS CORRETOS
 * Testa diferentes variações de endpoints para Evolution v2.3.0
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     🔍 DESCOBRIR ENDPOINTS - EVOLUTION v2.3.0         ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Parse .env
$env = [];
$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$evolutionUrl = $env['EVOLUTION_URL'];
$evolutionKey = $env['EVOLUTION_KEY'];

echo "🔧 CONFIGURAÇÃO:\n";
echo "   Evolution: $evolutionUrl\n";
echo "   API Key: ***...***\n\n";

// ============================================================
// TESTE 1: GET / (root)
// ============================================================
echo "📍 TESTE 1: GET / (verificar se API está viva)\n";
echo str_repeat("─", 55) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/",
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $evolutionKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";
echo "Resposta:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================================
// LISTA DE ENDPOINTS PARA TESTAR
// ============================================================

$endpoints = [
    // Variações de listagem
    'GET /connections',
    'GET /whatsapp',
    'GET /whatsapp/instances',
    'GET /connections/whatsapp/instances',
    'GET /instances',
    'GET /instance',
    
    // Variações de status
    'GET /whatsapp/status',
    'GET /status',
    
    // Swagger/Docs
    'GET /docs',
    'GET /docs/swagger',
];

echo "🔗 TESTANDO ENDPOINTS:\n";
echo str_repeat("─", 55) . "\n";

foreach ($endpoints as $endpoint) {
    [$method, $path] = explode(' ', $endpoint);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$evolutionUrl$path",
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $evolutionKey,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($httpCode >= 200 && $httpCode < 400) ? "✅" : "❌";
    echo "$status $endpoint - HTTP $httpCode\n";
    
    // Se deu certo, mostrar resposta
    if ($httpCode >= 200 && $httpCode < 400 && strpos($response, '{') === 0) {
        $data = json_decode($response, true);
        if ($data) {
            echo "   " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}

// ============================================================
// PRÓXIMAS AÇÕES
// ============================================================
echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║        🚀 PRÓXIMAS AÇÕES                             ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  Abra no navegador:\n";
echo "   http://localhost:8080/docs\n";
echo "   Veja todos os endpoints disponíveis\n\n";

echo "2️⃣  No Swagger, procure por:\n";
echo "   → 'instance' (criar/listar instâncias)\n";
echo "   → 'message' (enviar mensagens)\n";
echo "   → 'send' (enviar para WhatsApp)\n\n";

echo "3️⃣  Anote os endpoints corretos e avise-me:\n";
echo "   Exemplo: GET /whatsapp/instances\n";
echo "            POST /whatsapp/instance\n";
echo "            POST /whatsapp/message/sendText\n\n";

?>

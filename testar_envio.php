#!/usr/bin/env php
<?php

/**
 * 📤 TESTAR ENVIO DIRETO PARA EVOLUTION
 * Testa se Evolution consegue enviar mensagens
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     📤 TESTE DE ENVIO - EVOLUTION API                ║\n";
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

// Dados da mensagem
$instanceName = 'N8n'; // Nome da instância
$phone = '553199380844'; // Seu telefone para teste
$message = 'Olá! Teste do bot - ' . date('H:i:s');

echo "🔧 CONFIGURAÇÃO:\n";
echo "   Evolution: $evolutionUrl\n";
echo "   Instância: $instanceName\n";
echo "   Telefone: $phone\n";
echo "   Mensagem: $message\n\n";

// ============================================================
// TESTE 1: GET /instances
// ============================================================
echo "📍 TESTE 1: Listar instâncias (GET /instances)\n";
echo str_repeat("─", 55) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances",
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $evolutionKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Resposta: " . substr($response, 0, 200) . "\n";
}

// ============================================================
// TESTE 2: GET /instances/{instance}
// ============================================================
echo "\n📍 TESTE 2: Status da instância (GET /instances/$instanceName)\n";
echo str_repeat("─", 55) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances/$instanceName",
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $evolutionKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Resposta: " . substr($response, 0, 200) . "\n";
}

// ============================================================
// TESTE 3: POST /message/sendText/{instance}
// ============================================================
echo "\n📍 TESTE 3: Enviar mensagem (POST /message/sendText/$instanceName)\n";
echo str_repeat("─", 55) . "\n";

$payload = [
    'number' => $phone,
    'text' => $message
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/message/sendText/$instanceName",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $evolutionKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Status: HTTP $httpCode\n";
if ($error) {
    echo "❌ Erro cURL: $error\n";
} else {
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

// ============================================================
// TESTE 4: POST /instances/{instance}/send
// ============================================================
echo "\n📍 TESTE 4: Enviar via endpoint alternativo (POST /instances/$instanceName/send)\n";
echo str_repeat("─", 55) . "\n";

$payload = [
    'number' => $phone,
    'options' => [
        'text' => $message
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances/$instanceName/send",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $evolutionKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Status: HTTP $httpCode\n";
if ($error) {
    echo "❌ Erro cURL: $error\n";
} else {
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

// ============================================================
// RESUMO
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║                   📊 RESUMO DOS TESTES                 ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "✅ Se você vir uma mensagem chegando no seu WhatsApp, o problema foi resolvido!\n\n";
echo "❌ Se nenhuma mensagem chegar:\n";
echo "   1. Verifique se N8n está ativa no painel do Evolution\n";
echo "   2. Verifique se o WhatsApp está conectado\n";
echo "   3. Verifique o relatório acima para códigos de erro\n\n";

?>

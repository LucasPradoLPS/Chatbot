#!/usr/bin/env php
<?php

/**
 * 🔄 REATIVAR INSTÂNCIA N8N
 * Força a reconexão do WhatsApp
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     🔄 REATIVAR INSTÂNCIA N8N                        ║\n";
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
$instanceName = 'N8n';

echo "🔧 Tentando reativar instância '$instanceName'...\n\n";

// ============================================================
// OPÇÃO 1: DELETE /instances/{instance}
// ============================================================
echo "📍 OPÇÃO 1: Remover instância existente\n";
echo str_repeat("─", 55) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances/$instanceName",
    CURLOPT_CUSTOMREQUEST => 'DELETE',
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
if (in_array($httpCode, [200, 201, 204])) {
    echo "✅ Instância removida com sucesso\n";
} else {
    echo "⚠️  Resposta: " . substr($response, 0, 100) . "\n";
}

// ============================================================
// OPÇÃO 2: POST /instances (criar nova)
// ============================================================
echo "\n📍 OPÇÃO 2: Criar nova instância\n";
echo str_repeat("─", 55) . "\n";

$payload = [
    'instance_name' => $instanceName,
    'platform' => 'WHATSAPP',
    'webhook_url' => 'http://' . gethostbyname(gethostname()) . ':8000/webhook',
    'webhook_by_events' => true,
    'reject_call' => false,
    'msg_call' => 'Mensagens de voz não são suportadas'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
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
if ($httpCode === 201) {
    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if (isset($data['qrcode']) || isset($data['qr_code'])) {
        echo "\n🔲 QR CODE GERADO!\n";
        echo "   Use seu telefone para escanear o QR Code acima\n";
    }
} else {
    echo "⚠️  Erro: " . $response . "\n";
}

// ============================================================
// OPÇÃO 3: RESTART (reiniciar instância)
// ============================================================
echo "\n📍 OPÇÃO 3: Reiniciar instância\n";
echo str_repeat("─", 55) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$evolutionUrl/instances/$instanceName/restart",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([]),
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
if (in_array($httpCode, [200, 201])) {
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "⚠️  Resposta: " . substr($response, 0, 100) . "\n";
}

// ============================================================
// RESUMO
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║                🔧 PRÓXIMA AÇÃO                        ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  Se viu um QR Code acima:\n";
echo "   → Abra WhatsApp no seu telefone\n";
echo "   → Vá em: Configurações → Dispositivos conectados → Conectar um dispositivo\n";
echo "   → Escaneie o QR Code\n";
echo "   → Aguarde a conexão completar\n\n";

echo "2️⃣  Depois, teste novamente:\n";
echo "   php testar_envio.php\n\n";

echo "3️⃣  Se ainda não funcionar, acesse o painel:\n";
echo "   http://localhost:8080\n";
echo "   E verifique o status manualmente\n\n";

?>

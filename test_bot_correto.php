<?php
/**
 * Script de teste para testar o bot com instância N8n
 */

$mensagem = $argv[1] ?? 'Oi';

// Usar a instância correta: N8n
$numeroTeste = '5511999' . rand(100000, 999999);
$msgId = 'TEST_' . time() . '_' . rand(1000, 9999);

$payload = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => $numeroTeste . '@s.whatsapp.net',
            'id' => $msgId,
            'fromMe' => false,
        ],
        'message' => [
            'conversation' => $mensagem,
        ],
        'source' => 'test-script',
    ],
];

echo "═══════════════════════════════════════════════════════════\n";
echo "  TESTE DO BOT\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Instância: N8n\n";
echo "Mensagem: '{$mensagem}'\n";
echo "Número: {$numeroTeste}\n";
echo "Message ID: {$msgId}\n";
echo "Endpoint: http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert\n";
echo "───────────────────────────────────────────────────────────\n\n";

$ch = curl_init('http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Status HTTP: {$httpCode}\n";
if ($curlError) {
    echo "Erro cURL: {$curlError}\n";
}
echo "Resposta: {$response}\n\n";

if ($httpCode === 200 || $httpCode === 202) {
    echo "✓ Mensagem enviada com sucesso!\n";
    echo "Aguardando resposta do bot...\n";
} else {
    echo "✗ Erro ao enviar mensagem\n";
}

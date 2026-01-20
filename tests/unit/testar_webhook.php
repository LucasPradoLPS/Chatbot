<?php

$mensagem = $argv[1] ?? "Olá";
$numeroCliente = $argv[2] ?? "5511987654321";

echo "═══════════════════════════════════════════════════\n";
echo "📱 TESTE DO CHATBOT - ENVIAR MENSAGEM\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "📞 Número: $numeroCliente\n";
echo "💬 Mensagem: $mensagem\n";
echo "🔗 Servidor: http://192.168.3.3:8000\n\n";

$data = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => $numeroCliente . '@s.whatsapp.net',
            'senderPn' => $numeroCliente,
            'fromMe' => false,
            'id' => 'msg_' . time() . '_' . rand(1000, 9999),
        ],
        'pushName' => 'Teste Usuario',
        'message' => [
            'conversation' => $mensagem,
        ],
        'messageTimestamp' => time(),
        'source' => 'web',
    ],
];

echo "⏳ Enviando mensagem para o webhook...\n\n";

$ch = curl_init('http://192.168.3.3:8000/api/webhook/whatsapp');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "❌ ERRO DE CONEXÃO:\n";
    echo "   $curlError\n\n";
    exit(1);
}

echo "✅ Resposta HTTP: $httpCode\n";

if ($httpCode === 200) {
    echo "✅ Mensagem enviada com SUCESSO!\n\n";
    echo "📝 Resposta do servidor:\n";
    echo $response . "\n\n";
} else {
    echo "⚠️  Status inesperado: $httpCode\n";
    echo "Resposta:\n";
    echo $response . "\n\n";
}

echo "═══════════════════════════════════════════════════\n";
echo "✓ Teste concluído!\n";
echo "✓ Verifique os logs em: storage/logs/laravel.log\n";
echo "═══════════════════════════════════════════════════\n";

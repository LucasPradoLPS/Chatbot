<?php

/**
 * Teste simples de envio de imagem
 * Usa PHP curl para POST ao webhook
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  📸 TESTE SIMPLES: ENVIAR IMAGEM AO CHATBOT          ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$webhookUrl = 'http://127.0.0.1:8000/api/webhook/whatsapp';
$numeroTeste = '5511987654321';

$payload = [
    'instance' => 'seu_numero_whatsapp',
    'data' => [
        'key' => [
            'remoteJid' => $numeroTeste . '@s.whatsapp.net',
            'senderPn' => '55' . preg_replace('/\D/', '', $numeroTeste),
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'imageMessage' => [
                'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_full.jpg/640px-Fronalpstock_full.jpg',
                'mimetype' => 'image/jpeg',
                'caption' => 'Foto de teste',
                'mediaKey' => 'ABC123'
            ]
        ]
    ]
];

echo "🎯 ENVIANDO IMAGEM...\n";
echo "─────────────────────────────────────\n";
echo "URL: $webhookUrl\n";
echo "Imagem: https://upload.wikimedia.org/wikipedia/commons/...\n";
echo "Tipo: image/jpeg (paisagem natural)\n";
echo "De: $numeroTeste\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ ERRO ao conectar:\n";
    echo "   $error\n\n";
    exit(1);
}

echo "✅ RESPOSTA RECEBIDA!\n";
echo "   Status: $httpCode\n\n";

if ($httpCode == 200 || $httpCode == 202) {
    echo "🎉 SUCESSO!\n";
    echo "   Imagem foi enviada ao processador!\n\n";
} else {
    echo "⚠️  Status HTTP: $httpCode\n";
    echo "   Resposta: " . substr($response, 0, 200) . "\n\n";
}

echo "📋 PRÓXIMAS AÇÕES:\n";
echo "─────────────────────────────────────\n";
echo "1. Verifique se a imagem foi processada:\n";
echo "   ls storage/app/public/whatsapp_media/images/\n\n";
echo "2. Verifique os logs:\n";
echo "   tail -f storage/logs/laravel.log | grep -i image\n\n";
echo "3. Procure por 'MIDIA PROCESSADA' nos logs\n\n";

echo "🔍 DEBUGGING:\n";
echo "─────────────────────────────────────\n";
echo "Se não funcionou, verifique:\n";
echo "✓ Servidor está rodando? (porta 8000)\n";
echo "✓ OPENAI_KEY está em .env?\n";
echo "✓ Logs em storage/logs/laravel.log\n";
echo "✓ Pasta storage/app/public/whatsapp_media/ criada?\n\n";

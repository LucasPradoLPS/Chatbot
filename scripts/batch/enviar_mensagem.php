<?php

/**
 * Script para simular envio de mensagens ao chatbot
 * Uso: php enviar_mensagem.php "sua mensagem aqui"
 */

$mensagem = $argv[1] ?? null;

if (!$mensagem) {
    echo "❌ Erro: Você precisa informar a mensagem!\n\n";
    echo "Uso:\n";
    echo "  php enviar_mensagem.php \"Olá, gostaria de informações\"\n";
    echo "  php enviar_mensagem.php \"Quero alugar um imóvel\"\n\n";
    exit(1);
}

$telefone = $argv[2] ?? '5511999999999'; // Número padrão para testes

echo "📱 Enviando mensagem ao chatbot...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📞 Telefone: {$telefone}\n";
echo "💬 Mensagem: {$mensagem}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Simula o webhook da Evolution API
$webhookData = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => $telefone . '@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'MSG_' . time() . '_' . rand(1000, 9999),
        ],
        'pushName' => 'Teste',
        'message' => [
            'conversation' => $mensagem,
        ],
        'messageTimestamp' => time(),
        'source' => 'web',
    ],
    'destination' => 'http://localhost:8000/api/webhook/whatsapp',
    'date_time' => date('Y-m-d H:i:s'),
    'sender' => $telefone,
    'server_url' => 'http://localhost:8000',
];

// Envia para o webhook
$ch = curl_init('http://localhost:8000/api/webhook/whatsapp');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

echo "⏳ Aguardando resposta do bot...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erro ao conectar com o chatbot:\n";
    echo "   {$error}\n\n";
    echo "💡 Verifique se o servidor Laravel está rodando:\n";
    echo "   php artisan serve\n\n";
    exit(1);
}

if ($httpCode === 200) {
    echo "✅ Mensagem enviada com sucesso!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Status HTTP: {$httpCode}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "📝 Resposta: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "💡 Próximos passos:\n";
    echo "   1. Verifique os logs: tail -n 50 storage/logs/laravel.log\n";
    echo "   2. Ou abra: storage/logs/laravel.log\n";
    echo "   3. A resposta do bot será enviada via Evolution API\n\n";
} else {
    echo "⚠️  Resposta recebida com código: {$httpCode}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo $response . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

echo "🔍 Para ver os detalhes completos:\n";
echo "   php artisan queue:failed  (jobs falhados)\n";
echo "   cat storage/logs/laravel.log | tail -n 100\n\n";

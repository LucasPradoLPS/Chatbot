<?php
/**
 * Script de teste para enviar mensagem "Oi" ou "Olá" e testar a saudação personalizada
 * 
 * Uso: php test_saudacao.php [oi|ola]
 */

$mensagem = $argv[1] ?? 'oi';
$mensagemFormatada = strtolower($mensagem) === 'ola' ? 'Olá' : 'Oi';

// Gerar um número de telefone de teste único para cada execução
$numeroTeste = '5511999' . rand(100000, 999999);

$payload = [
    'instance' => 'N8n', // Ajuste conforme sua instância
    'data' => [
        'key' => [
            'remoteJid' => $numeroTeste . '@s.whatsapp.net',
            'id' => 'TEST_' . time() . '_' . rand(1000, 9999),
            'fromMe' => false,
        ],
        'message' => [
            'conversation' => $mensagemFormatada,
        ],
        'source' => 'test-script',
    ],
];

echo "═══════════════════════════════════════════════════════════\n";
echo "  TESTE DE SAUDAÇÃO PERSONALIZADA\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Enviando mensagem: '{$mensagemFormatada}'\n";
echo "Número de teste: {$numeroTeste}\n";
echo "Endpoint: http://127.0.0.1:8000/api/webhook/whatsapp\n";
echo "───────────────────────────────────────────────────────────\n\n";

$ch = curl_init('http://127.0.0.1:8000/api/webhook/whatsapp');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status HTTP: {$httpCode}\n";
echo "Resposta da API: {$response}\n\n";

if ($httpCode === 200) {
    echo "✓ Mensagem enviada com sucesso!\n";
    echo "✓ Verifique os logs para ver a resposta do bot:\n";
    echo "  - tail -f storage/logs/laravel.log\n";
    echo "  - Ou acesse: http://127.0.0.1:8000/api/debug/logs/laravel.log\n\n";
    echo "✓ O bot deve responder com '{$mensagemFormatada}!' no início da mensagem.\n";
} else {
    echo "✗ Erro ao enviar mensagem\n";
    echo "  Verifique se o servidor está rodando em http://127.0.0.1:8000\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "Para testar com outra saudação:\n";
echo "  php test_saudacao.php oi   → Bot responderá com 'Oi!'\n";
echo "  php test_saudacao.php ola  → Bot responderá com 'Olá!'\n";
echo "═══════════════════════════════════════════════════════════\n";

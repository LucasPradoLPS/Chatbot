<?php

/**
 * Script para testar saudação com nome
 * Simula uma mensagem de saudação com pushName
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Jobs\ProcessWhatsappMessage;

echo "═══════════════════════════════════════════════════════════\n";
echo "        TESTE - SAUDAÇÃO COM NOME DO CLIENTE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Exemplo 1: Cliente diz "Olá" - com pushName
echo "📋 TESTE 1: Cliente envia 'Olá' com nome 'Lucas Prado'\n\n";

$payload1 = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => '5511999785770@s.whatsapp.net',
            'senderPn' => '5511999785770@s.whatsapp.net',
            'id' => 'TEST_' . uniqid(),
            'fromMe' => false,
        ],
        'pushName' => 'Lucas Prado', // Nome do cliente
        'message' => [
            'conversation' => 'Olá',
        ],
        'source' => 'test-script',
    ],
];

echo "Enviando payload:\n";
echo "- Cliente: 5511999785770\n";
echo "- Nome (pushName): " . $payload1['data']['pushName'] . "\n";
echo "- Mensagem: " . $payload1['data']['message']['conversation'] . "\n\n";

echo "Esperado na resposta:\n";
echo "❌ Resposta genérica: 'Olá! Eu sou o assistente...'\n";
echo "✅ Resposta com nome: 'Olá Lucas Prado! Eu sou o assistente...'\n\n";

try {
    $job = new ProcessWhatsappMessage($payload1);
    $job->handle();
    echo "✅ Mensagem processada com sucesso!\n";
    echo "Verifique nos logs: storage/logs/laravel.log\n";
    echo "Procure por '[SAUDACAO]' ou '[INTENT]'\n\n";
} catch (\Exception $e) {
    echo "⚠️ Erro ao processar: " . $e->getMessage() . "\n\n";
}

// Exemplo 2: Cliente diz "Oi" - com pushName diferente
echo "───────────────────────────────────────────────────────────\n\n";
echo "📋 TESTE 2: Cliente envia 'Oi' com nome 'Maria Silva'\n\n";

$payload2 = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => '5521987654321@s.whatsapp.net',
            'senderPn' => '5521987654321@s.whatsapp.net',
            'id' => 'TEST_' . uniqid(),
            'fromMe' => false,
        ],
        'pushName' => 'Maria Silva', // Nome diferente
        'message' => [
            'conversation' => 'Oi',
        ],
        'source' => 'test-script',
    ],
];

echo "Enviando payload:\n";
echo "- Cliente: 5521987654321\n";
echo "- Nome (pushName): " . $payload2['data']['pushName'] . "\n";
echo "- Mensagem: " . $payload2['data']['message']['conversation'] . "\n\n";

echo "Esperado na resposta:\n";
echo "✅ Resposta com nome: 'Oi Maria Silva! Eu sou o assistente...'\n\n";

try {
    $job = new ProcessWhatsappMessage($payload2);
    $job->handle();
    echo "✅ Mensagem processada com sucesso!\n";
    echo "Verifique nos logs: storage/logs/laravel.log\n\n";
} catch (\Exception $e) {
    echo "⚠️ Erro ao processar: " . $e->getMessage() . "\n\n";
}

// Exemplo 3: Cliente diz saudação - SEM pushName
echo "───────────────────────────────────────────────────────────\n\n";
echo "📋 TESTE 3: Cliente envia 'Olá' SEM nome (pushName nulo)\n\n";

$payload3 = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => '5585999111222@s.whatsapp.net',
            'senderPn' => '5585999111222@s.whatsapp.net',
            'id' => 'TEST_' . uniqid(),
            'fromMe' => false,
        ],
        // Sem pushName - será null
        'message' => [
            'conversation' => 'Olá',
        ],
        'source' => 'test-script',
    ],
];

echo "Enviando payload:\n";
echo "- Cliente: 5585999111222\n";
echo "- Nome (pushName): (nulo - sem nome disponível)\n";
echo "- Mensagem: " . $payload3['data']['message']['conversation'] . "\n\n";

echo "Esperado na resposta:\n";
echo "✅ Resposta com fallback: 'Olá visitante! Eu sou o assistente...'\n";
echo "   (Note: O bot detectará que não tem nome e usará 'visitante')\n\n";

try {
    $job = new ProcessWhatsappMessage($payload3);
    $job->handle();
    echo "✅ Mensagem processada com sucesso!\n";
    echo "Verifique nos logs: storage/logs/laravel.log\n\n";
} catch (\Exception $e) {
    echo "⚠️ Erro ao processar: " . $e->getMessage() . "\n\n";
}

// Resumo
echo "═══════════════════════════════════════════════════════════\n\n";
echo "🎯 RESUMO DAS MUDANÇAS\n\n";
echo "✅ ProcessWhatsappMessage.php:\n";
echo "   - Extrai 'pushName' do payload do WhatsApp\n";
echo "   - Passa nome para a etapa 'boas_vindas'\n\n";
echo "✅ StateMachine.php:\n";
echo "   - STATE_PROMPTS incluem 'Olá {nomeCliente}!' na saudação\n\n";
echo "✅ Comportamento do Bot:\n";
echo "   1. Se cliente enviar 'Olá' e tem nome → 'Olá [Nome]!'\n";
echo "   2. Se cliente enviar 'Oi' e tem nome → 'Oi [Nome]!'\n";
echo "   3. Se não tem pushName → 'Olá visitante!' (fallback)\n\n";
echo "═══════════════════════════════════════════════════════════\n";

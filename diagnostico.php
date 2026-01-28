#!/usr/bin/env php
<?php

/**
 * 🔍 SCRIPT DE DIAGNÓSTICO SIMPLIFICADO
 * Identifica problemas de entrega de mensagens
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     🔍 DIAGNÓSTICO DO CHATBOT - v1.0                 ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// ============================================================
// 1️⃣ VERIFICAR .ENV
// ============================================================
echo "📋 [1/4] VERIFICANDO .ENV...\n";
echo str_repeat("─", 55) . "\n";

$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ Arquivo .env não encontrado!\n\n";
    exit(1);
}

// Parse .env manualmente
$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$evolutionUrl = $env['EVOLUTION_URL'] ?? null;
$evolutionKey = $env['EVOLUTION_KEY'] ?? null;
$openaiKey = $env['OPENAI_KEY'] ?? null;

echo "✅ Evolution URL: " . ($evolutionUrl ? "✓" : "✗") . " $evolutionUrl\n";
echo "✅ Evolution Key: " . ($evolutionKey ? "✓" : "✗") . " (***...)\n";
echo "✅ OpenAI Key: " . ($openaiKey ? "✓" : "✗") . " (***...)\n\n";

// ============================================================
// 2️⃣ VERIFICAR CONEXÃO COM EVOLUTION
// ============================================================
echo "🌐 [2/4] TESTANDO EVOLUTION...\n";
echo str_repeat("─", 55) . "\n";

if (!$evolutionUrl || !$evolutionKey) {
    echo "❌ Configurações não encontradas no .env\n\n";
    exit(1);
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $evolutionUrl . '/health',
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

if ($httpCode === 200 || $httpCode === 404) {
    echo "✅ Evolution está respondendo (HTTP $httpCode)\n";
    $evolutionAlive = true;
} else {
    echo "❌ Evolution não responde (HTTP $httpCode)\n";
    echo "   Certifique-se de que Evolution está rodando em: $evolutionUrl\n\n";
    $evolutionAlive = false;
}

// ============================================================
// 3️⃣ LISTAR INSTÂNCIAS
// ============================================================
echo "\n🔑 [3/4] VERIFICANDO INSTÂNCIAS...\n";
echo str_repeat("─", 55) . "\n";

if ($evolutionAlive) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $evolutionUrl . '/instances',
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
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $instances = $data['instances'] ?? $data;
        
        if (is_array($instances) && !empty($instances)) {
            echo "✅ Instâncias encontradas:\n";
            foreach ($instances as $inst) {
                $name = $inst['instance_name'] ?? $inst['name'] ?? 'desconhecida';
                $state = $inst['state'] ?? $inst['status'] ?? 'unknown';
                $stateEmoji = ($state === 'open' || $state === 'connected') ? '✅' : '⚠️';
                echo "   $stateEmoji $name (Estado: $state)\n";
            }
        } else {
            echo "❌ Nenhuma instância encontrada\n";
            echo "   Você precisa criar uma instância 'N8n' no painel do Evolution\n";
        }
    } else {
        echo "⚠️  Erro ao listar instâncias (HTTP $httpCode)\n";
    }
} else {
    echo "⚠️  Pulando (Evolution não respondendo)\n";
}

// ============================================================
// 4️⃣ VERIFICAR LOGS
// ============================================================
echo "\n📊 [4/4] ANALISANDO LOGS...\n";
echo str_repeat("─", 55) . "\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $lines = array_reverse(explode("\n", $content));
    
    $pendingCount = 0;
    $sentCount = 0;
    $errorCount = 0;
    
    foreach (array_slice($lines, 0, 1000) as $line) {
        if (strpos($line, '"status":"PENDING"') !== false) {
            $pendingCount++;
        }
        if (strpos($line, 'Resposta da API Evolution') !== false) {
            $sentCount++;
        }
        if (preg_match('/\[ERROR\]|\[error\]/', $line)) {
            $errorCount++;
        }
    }
    
    echo "📨 Mensagens PENDING: $pendingCount\n";
    echo "📤 Mensagens enviadas: $sentCount\n";
    echo "⚠️  Linhas com erro: $errorCount\n";
    
    if ($pendingCount > 5) {
        echo "\n❌ PROBLEMA IDENTIFICADO:\n";
        echo "   Muitas mensagens ficando com status PENDING!\n";
        echo "   Isso significa que o Evolution está recebendo as requisições\n";
        echo "   mas NÃO conseguindo enviar para o WhatsApp.\n\n";
        echo "🔧 SOLUÇÃO:\n";
        echo "   1. Verifique se a instância N8n está ativa no Evolution\n";
        echo "   2. Verifique se o QR Code foi escaneado corretamente\n";
        echo "   3. Acesse: http://localhost:8080\n";
    }
} else {
    echo "⚠️  Arquivo de log não encontrado\n";
}

// ============================================================
// RESUMO
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║            🔧 PRÓXIMAS AÇÕES RECOMENDADAS              ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  VERIFICAR EVOLUTION:\n";
echo "   Acesse: http://localhost:8080\n";
echo "   → Procure pela instância 'N8n'\n";
echo "   → Verifique se está 'ATIVA' ou 'CONECTADA'\n\n";

echo "2️⃣  ATIVAR INSTÂNCIA (se necessário):\n";
echo "   → Se inativa: clique para ativar\n";
echo "   → Escaneie o QR Code com WhatsApp\n";
echo "   → Aguarde a conexão completar\n\n";

echo "3️⃣  REINICIAR LARAVEL:\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan serve --port=8000\n\n";

echo "4️⃣  MONITORAR LOGS:\n";
echo "   tail -f storage/logs/laravel.log\n\n";

echo "5️⃣  TESTAR ENVIO:\n";
echo "   php enviar_mensagem.php \"Olá, teste\"\n\n";

exit(0);

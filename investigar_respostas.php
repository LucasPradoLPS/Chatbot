<?php

/**
 * INVESTIGAÇÃO: Por que não estou recebendo as respostas do bot?
 * 
 * Este script verifica:
 * 1. Se o webhook está configurado na Evolution
 * 2. Se o servidor Laravel está respondendo
 * 3. Se há logs de tentativa de envio
 * 4. Status da fila de mensagens
 */

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 INVESTIGAÇÃO: Por que não recebo respostas?            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require 'vendor/autoload.php';

// Carrega variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$evolutionUrl = $_ENV['EVOLUTION_URL'] ?? 'http://localhost:8080';
$evolutionKey = $_ENV['EVOLUTION_KEY'] ?? '';
$laravelUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$laravelPort = 8000;

echo "⚙️  INFORMAÇÕES DO SISTEMA:\n";
echo "   Evolution URL: $evolutionUrl\n";
echo "   Laravel URL: $laravelUrl:$laravelPort\n\n";

// ==== TESTE 1: Servidor Laravel Está Rodando? ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1️⃣  TESTE: Laravel está rodando?\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $ch = curl_init("$laravelUrl:$laravelPort/api/ping");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ ERRO: Não consegui conectar ao Laravel\n";
        echo "   Erro: $error\n\n";
        echo "💡 O servidor Laravel NÃO está rodando!\n";
        echo "   Execute em outro terminal:\n";
        echo "   ➜ php artisan serve\n\n";
    } elseif ($httpCode == 0) {
        echo "❌ ERRO: Conexão recusada\n";
        echo "   O servidor Laravel não está escutando em $laravelUrl:$laravelPort\n\n";
        echo "💡 Soluções:\n";
        echo "   1. Execute: php artisan serve\n";
        echo "   2. Ou verifique se está em: http://localhost:8000\n\n";
    } else {
        echo "✅ Laravel está rodando! (HTTP $httpCode)\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
}

// ==== TESTE 2: Webhook Está Configurado? ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2️⃣  TESTE: Webhook está configurado na Evolution?\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $ch = curl_init($evolutionUrl . '/instances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $evolutionKey]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $instances = json_decode($response, true) ?? [];
    
    if (!empty($instances)) {
        $inst = $instances[0];
        $webhookUrl = $inst['instance']['webhook'] ?? null;
        $webhookInfo = $inst['webhookUrl'] ?? null;
        
        echo "   Instância: " . ($inst['instance']['name'] ?? 'N/A') . "\n";
        echo "   Webhook URL: " . ($webhookUrl ?? $webhookInfo ?? '❌ NÃO CONFIGURADO') . "\n\n";
        
        if (!$webhookUrl && !$webhookInfo) {
            echo "❌ WEBHOOK NÃO ESTÁ CONFIGURADO!\n\n";
            echo "💡 Como configurar:\n";
            echo "   1. Acesse: http://localhost:8080\n";
            echo "   2. Clique na instância (ex: N8n)\n";
            echo "   3. Procure por 'Webhook' ou 'Webhooks'\n";
            echo "   4. Adicione a URL:\n";
            echo "      http://host.docker.internal:8000/api/webhook/whatsapp\n";
            echo "   5. Salve as configurações\n\n";
        } else {
            echo "✅ Webhook configurado!\n";
            echo "   URL: $webhookUrl\n\n";
        }
    }
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
}

// ==== TESTE 3: Verificar Logs Recentes ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "3️⃣  TESTE: Últimos eventos nos logs\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    $lines = array_reverse(file($logFile));
    $recentLines = array_slice($lines, 0, 20);
    
    echo "   Últimas 5 mensagens nos logs:\n\n";
    $count = 0;
    foreach ($recentLines as $line) {
        if (strpos($line, 'Webhook received') !== false || 
            strpos($line, 'ProcessWhatsappMessage') !== false ||
            strpos($line, '[BLOQUEADO]') !== false ||
            strpos($line, 'ERROR') !== false) {
            echo "   " . trim($line) . "\n";
            $count++;
            if ($count >= 5) break;
        }
    }
    
    if ($count === 0) {
        echo "   ⚠️  Nenhuma mensagem recebida recentemente\n\n";
        echo "💡 Isto significa:\n";
        echo "   - Webhook NÃO está enviando dados para o Laravel\n";
        echo "   - Configure o webhook na Evolution (ver passo 2 acima)\n\n";
    }
} else {
    echo "   ⚠️  Arquivo de log não encontrado: $logFile\n\n";
}

// ==== TESTE 4: Verificar Fila de Mensagens ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "4️⃣  TESTE: Fila de mensagens pendentes\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Carregar Laravel
    $app = require __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Verificar se há jobs na fila
    $db = $app->make('db');
    
    // Verificar jobs na tabela jobs
    $pendingJobs = $db->table('jobs')->count();
    $failedJobs = $db->table('failed_jobs')->count();
    
    echo "   ✅ Jobs na fila: $pendingJobs\n";
    echo "   ⚠️  Jobs falhados: $failedJobs\n\n";
    
    if ($failedJobs > 0) {
        echo "💡 Há jobs falhados! Verifique por erros.\n";
        echo "   Execute: php artisan queue:failed\n\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Não consegui verificar fila\n\n";
}

// ==== RESUMO FINAL ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 CHECKLIST FINAL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Verifique na ordem:\n\n";

echo "1️⃣  [ ] Laravel rodando em http://localhost:8000\n";
echo "     Comando: php artisan serve\n\n";

echo "2️⃣  [ ] Webhook configurado na Evolution\n";
echo "     URL: http://host.docker.internal:8000/api/webhook/whatsapp\n";
echo "     (ou seu IP local: http://192.168.x.x:8000/api/webhook/whatsapp)\n\n";

echo "3️⃣  [ ] Enviou uma mensagem pelo WhatsApp\n";
echo "     Envie uma mensagem simples (ex: 'Olá')\n\n";

echo "4️⃣  [ ] Verifique os logs:\n";
echo "     storage/logs/laravel.log\n";
echo "     Procure por: 'Webhook received' ou 'ProcessWhatsappMessage'\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Depois de configurar, envie uma mensagem de teste pelo WhatsApp\n";
echo "e me mostra qual é o erro que vê nos logs!\n\n";

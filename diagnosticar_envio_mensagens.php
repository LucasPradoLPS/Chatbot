<?php

/**
 * DIAGNÓSTICO: Por que o bot não está respondendo?
 * 
 * Este script testa:
 * 1. Conexão com Evolution API
 * 2. Instâncias ativas no Evolution
 * 3. Configuração do webhook
 * 4. Envio de teste via Evolution
 */

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 DIAGNÓSTICO: Por que o bot não responde?              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require 'vendor/autoload.php';

// Carrega variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$evolutionUrl = $_ENV['EVOLUTION_URL'] ?? 'http://localhost:8080';
$evolutionKey = $_ENV['EVOLUTION_KEY'] ?? '';
$numero = $argv[1] ?? '553199380844';

echo "⚙️  CONFIGURAÇÕES:\n";
echo "   Evolution URL: $evolutionUrl\n";
echo "   Evolution Key: " . substr($evolutionKey, 0, 10) . "...\n";
echo "   Número de teste: $numero\n\n";

// ==== TESTE 1: Conexão Básica ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1️⃣  TESTE: Conectar ao Evolution API\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $ch = curl_init($evolutionUrl . '/instances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $evolutionKey]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ ERRO DE CONEXÃO:\n";
        echo "   $error\n\n";
        echo "💡 Possíveis soluções:\n";
        echo "   1. A Evolution API não está rodando\n";
        echo "   2. URL errada: $evolutionUrl\n";
        echo "   3. Firewall bloqueando a porta\n\n";
    } elseif ($httpCode == 401 || $httpCode == 403) {
        echo "❌ ERRO DE AUTENTICAÇÃO (HTTP $httpCode):\n";
        echo "   A chave Evolution API está incorreta!\n";
        echo "   Chave configurada: " . substr($evolutionKey, 0, 20) . "...\n\n";
    } elseif ($httpCode == 200) {
        echo "✅ SUCESSO! Conectado ao Evolution API\n";
        $instances = json_decode($response, true);
        echo "   Instâncias ativas: " . count($instances) . "\n";
        foreach ($instances as $inst) {
            echo "   ├─ Nome: " . ($inst['instance']['name'] ?? 'N/A') . "\n";
            echo "   │  Status: " . ($inst['instance']['state'] ?? 'N/A') . "\n";
        }
        echo "\n";
    } else {
        echo "⚠️  Resposta inesperada (HTTP $httpCode):\n";
        echo "   " . substr($response, 0, 200) . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n\n";
}

// ==== TESTE 2: Listar Instâncias ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2️⃣  TESTE: Instâncias Disponíveis\n";
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
    
    if (empty($instances)) {
        echo "❌ NENHUMA INSTÂNCIA ENCONTRADA!\n\n";
        echo "💡 O que fazer:\n";
        echo "   1. Acesse http://localhost:8080 no navegador\n";
        echo "   2. Crie uma nova instância (ex: N8n)\n";
        echo "   3. Ative o WhatsApp nela\n";
        echo "   4. Escaneie o QR Code com seu celular\n\n";
    } else {
        echo "✅ Instâncias disponíveis:\n\n";
        foreach ($instances as $inst) {
            $name = $inst['instance']['name'] ?? 'Desconhecido';
            $state = $inst['instance']['state'] ?? 'unknown';
            echo "   📱 $name - Estado: $state\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
}

// ==== TESTE 3: Testar Envio de Mensagem ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "3️⃣  TESTE: Enviar Mensagem de Teste\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$instancia = 'N8n'; // Mudar se sua instância tem outro nome

$payload = [
    'number' => $numero,
    'text' => '✅ Teste do Bot - Se recebeu essa mensagem, está funcionando!',
];

try {
    $ch = curl_init($evolutionUrl . "/message/sendText/$instancia");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $evolutionKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   Número: $numero\n";
    echo "   Instância: $instancia\n";
    echo "   HTTP Status: $httpCode\n\n";
    
    if ($error) {
        echo "❌ ERRO: $error\n\n";
    } elseif ($httpCode == 201 || $httpCode == 200) {
        echo "✅ SUCESSO! Mensagem enviada!\n";
        echo "   Resposta: " . substr($response, 0, 300) . "\n\n";
        echo "💡 Se não recebeu no WhatsApp, o problema está no webhook!\n";
        echo "   - Verifique se o webhook está configurado na Evolution\n";
        echo "   - Configure para: http://SEU_IP:8000/api/webhook/whatsapp\n\n";
    } else {
        echo "⚠️  Resposta inesperada (HTTP $httpCode)\n";
        echo "   Resposta: " . substr($response, 0, 500) . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n\n";
}

// ==== RESUMO ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 RESUMO DO DIAGNÓSTICO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Se o bot não está respondendo, verifique na ordem:\n\n";
echo "1️⃣  Evolution API está rodando?\n";
echo "    → Acesse http://localhost:8080 no navegador\n\n";

echo "2️⃣  Tem uma instância ativa com WhatsApp?\n";
echo "    → A Evolution deve mostrar uma instância 'connected'\n\n";

echo "3️⃣  O webhook está configurado?\n";
echo "    → Na Evolution, configure o webhook para:\n";
echo "    → http://SEU_IP:8000/api/webhook/whatsapp\n\n";

echo "4️⃣  O servidor Laravel está rodando?\n";
echo "    → Execute: php artisan serve\n\n";

echo "5️⃣  O banco de dados está funcionando?\n";
echo "    → Execute: php artisan migrate\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";

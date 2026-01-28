<?php
/**
 * Script para diagnosticar problemas de envio via Evolution API
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  🔍 DIAGNOSTICANDO ENVIO EVOLUTION API                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Configurações
$evolutionUrl = config('services.evolution.url');
$evolutionKey = config('services.evolution.key');
$instance = 'N8n';
$clienteId = '553199380844'; // Número que enviou mensagem nos logs

echo "📋 CONFIGURAÇÕES\n";
echo "────────────────────\n";
echo "URL: $evolutionUrl\n";
echo "Key: " . substr($evolutionKey ?? 'NÃO CONFIGURADA', 0, 15) . "...\n";
echo "Instance: $instance\n";
echo "Cliente ID: $clienteId\n\n";

// Teste 1: Enviar mensagem de texto
echo "📨 TESTE 1: Enviando mensagem de texto\n";
echo "────────────────────────────────────────\n";

try {
    $response = Http::timeout(30)
        ->withHeaders(['apikey' => $evolutionKey])
        ->post("$evolutionUrl/message/sendText/$instance", [
            'number' => $clienteId,
            'text' => '🤖 Teste de envio - ' . date('H:i:s'),
        ]);

    echo "✅ Resposta: {$response->status()}\n";
    echo "Body: " . substr($response->body(), 0, 300) . "\n\n";
    
    if ($response->status() !== 200) {
        echo "⚠️ Status não é 200\n";
        echo "Resposta completa:\n";
        echo $response->body() . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n\n";
}

// Teste 2: Verificar status da instância
echo "📱 TESTE 2: Verificando status da instância\n";
echo "──────────────────────────────────────────\n";

try {
    $response = Http::timeout(30)
        ->withHeaders(['apikey' => $evolutionKey])
        ->get("$evolutionUrl/instance/info/$instance");

    echo "✅ Resposta: {$response->status()}\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Instance: {$data['instance']['instanceName']}\n";
        echo "Status: {$data['instance']['status']}\n";
        echo "Connected: " . ($data['instance']['connectionStatus'] === 'open' ? 'SIM ✓' : 'NÃO ✗') . "\n";
    } else {
        echo "Erro: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║  💡 RECOMENDAÇÕES                                        ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "Se você recebeu \"Resposta enviada com sucesso\" nos logs mas\n";
echo "não vê a mensagem no WhatsApp, as causas podem ser:\n\n";

echo "1️⃣  Evolution API não conseguiu enviar:\n";
echo "    - Verifique se a instância N8n está CONECTADA\n";
echo "    - Verifique se o QR Code foi escaneado\n\n";

echo "2️⃣  Número incorreto:\n";
echo "    - O botVerifique se o cliente_id está correto\n";
echo "    - Números devem estar sem formatação: 5511987654321\n\n";

echo "3️⃣  Webhook não está apontando para o lugar certo:\n";
echo "    - Evolution API enviando para: host.docker.internal\n";
echo "    - Isso indica que pode estar em Docker\n\n";

echo "PRÓXIMOS PASSOS:\n";
echo "1. Verifique se Evolution API está rodando\n";
echo "2. Verifique se a instância N8n está conectada\n";
echo "3. Verifique se o webhook está configurado corretamente\n";

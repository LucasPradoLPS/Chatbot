<?php
/**
 * Script de teste direto do chatbot
 * Não precisa do servidor estar respondendo HTTP
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  🤖 TESTE DO CHATBOT DIRETAMENTE                       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar banco de dados
echo "✅ PASSO 1: VERIFICANDO BANCO DE DADOS\n";
echo "────────────────────────────────────────\n";
try {
    $count = DB::table('agentes')->count();
    echo "✓ Agentes no banco: $count\n";
    
    $instancias = DB::table('instancia_whatsapps')->count();
    echo "✓ Instâncias WhatsApp: $instancias\n";
    
    $empresas = DB::table('empresas')->count();
    echo "✓ Empresas: $empresas\n";
    
} catch (Exception $e) {
    echo "✗ Erro ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar arquivo .env
echo "\n✅ PASSO 2: VERIFICANDO CONFIGURAÇÕES (.env)\n";
echo "────────────────────────────────────────────\n";

$openaiKey = env('OPENAI_KEY');
if ($openaiKey) {
    echo "✓ OPENAI_KEY configurada: " . substr($openaiKey, 0, 10) . "...\n";
} else {
    echo "✗ OPENAI_KEY não configurada!\n";
}

$evolutionUrl = env('EVOLUTION_URL');
if ($evolutionUrl) {
    echo "✓ EVOLUTION_URL: $evolutionUrl\n";
} else {
    echo "✗ EVOLUTION_URL não configurada!\n";
}

$evolutionKey = env('EVOLUTION_KEY');
if ($evolutionKey) {
    echo "✓ EVOLUTION_KEY configurada: " . substr($evolutionKey, 0, 10) . "...\n";
} else {
    echo "✗ EVOLUTION_KEY não configurada!\n";
}

// 3. Testar uma mensagem de forma direta
echo "\n✅ PASSO 3: SIMULANDO PROCESSAMENTO DE MENSAGEM\n";
echo "────────────────────────────────────────────────\n";

// Obter primeira empresa e agente
$empresa = DB::table('empresas')->first();
if (!$empresa) {
    echo "✗ Nenhuma empresa configurada!\n";
    exit(1);
}

$agente = DB::table('agentes')->where('empresa_id', $empresa->id)->first();
if (!$agente) {
    echo "✗ Nenhum agente encontrado para a empresa!\n";
    exit(1);
}

echo "📦 Usando:\n";
echo "   - Empresa: {$empresa->nome}\n";
echo "   - Agente ID: {$agente->id}\n";
echo "   - IA Ativa: " . ($agente->ia_ativa ? 'Sim' : 'Não') . "\n\n";

// Simular um webhook de mensagem
$webhookPayload = [
    'instance' => 'N8n',
    'data' => [
        'key' => [
            'remoteJid' => '5511987654321@s.whatsapp.net',
            'senderPn' => '5511987654321',
            'id' => 'msg_' . uniqid(),
            'fromMe' => false
        ],
        'message' => [
            'conversation' => 'Olá, quero informações sobre apartamentos disponíveis'
        ]
    ]
];

echo "🔄 Payload enviado:\n";
echo json_encode($webhookPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Tentar processar a mensagem
try {
    echo "📨 Processando mensagem...\n";
    
    // Simular o que o webhook faria
    $remoteJid = $webhookPayload['data']['key']['remoteJid'];
    $phoneNumber = $webhookPayload['data']['key']['senderPn'];
    $message = $webhookPayload['data']['message']['conversation'];
    
    echo "   Número: $phoneNumber\n";
    echo "   Mensagem: $message\n";
    
    // Verificar se a instância existe
    $instancia = DB::table('instancia_whatsapps')
        ->where('instance_name', $webhookPayload['instance'])
        ->first();
    
    if (!$instancia) {
        echo "\n⚠️  Instância '{$webhookPayload['instance']}' não encontrada\n";
        echo "   Instâncias disponíveis:\n";
        $instancias = DB::table('instancia_whatsapps')->get();
        foreach ($instancias as $inst) {
            echo "   - {$inst->instance_name} (Empresa ID: {$inst->empresa_id})\n";
        }
    } else {
        echo "   ✓ Instância encontrada: {$instancia->instance_name}\n";
    }
    
    echo "\n✅ Teste de simulação concluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao processar: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Instruções para teste real
echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  📱 PRÓXIMOS PASSOS PARA TESTE REAL                     ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  Configure a Evolution API:\n";
echo "   URL: " . ($evolutionUrl ?? 'NÃO CONFIGURADA') . "\n";
echo "   Instance: N8n (ou use a instância que está configurada)\n\n";

echo "2️⃣  Configure o webhook:\n";
echo "   Apontando para: http://SEU_IP:8000/api/webhook/whatsapp\n";
echo "   Seu IP local: 192.168.3.3\n\n";

echo "3️⃣  Envie uma mensagem via WhatsApp para seu bot\n\n";

echo "4️⃣  Monitore os logs:\n";
echo "   tail -f storage/logs/laravel.log\n\n";

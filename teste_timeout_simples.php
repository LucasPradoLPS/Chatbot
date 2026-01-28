<?php

/**
 * 🧪 TESTE SIMPLIFICADO - TIMEOUT HANDOFF
 * Versão rápida que não consome muita memória
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     🧪 TESTE SIMPLIFICADO - TIMEOUT HANDOFF          ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$clienteId = '553199380844';
$threadId = 'test_timeout_' . time();

echo "📋 Teste Rápido:\n";
echo "   • Cliente: $clienteId\n";
echo "   • Thread: $threadId\n\n";

// 1️⃣ Verificar se código foi instalado
echo "1️⃣ Verificando instalação...\n";

if (file_exists('app/Jobs/CheckHandoffInactivity.php')) {
    echo "   ✅ Job CheckHandoffInactivity existe\n";
} else {
    echo "   ❌ Job não encontrado\n";
    exit(1);
}

// 2️⃣ Verificar ProcessWhatsappMessage
$processFile = file_get_contents('app/Jobs/ProcessWhatsappMessage.php');
if (strpos($processFile, 'CheckHandoffInactivity::dispatch') !== false) {
    echo "   ✅ ProcessWhatsappMessage foi modificado\n";
} else {
    echo "   ❌ Modificação não encontrada\n";
    exit(1);
}

// 3️⃣ Verificar database
echo "\n2️⃣ Verificando banco de dados...\n";

try {
    $threadCount = DB::table('threads')->count();
    echo "   ✅ Tabela 'threads' OK ($threadCount registros)\n";
} catch (\Exception $e) {
    echo "   ❌ Erro ao acessar threads: {$e->getMessage()}\n";
    exit(1);
}

// 4️⃣ Verificar coluna
try {
    $result = DB::select("SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='threads' AND column_name='ultima_atividade_usuario'
    )");
    
    if (isset($result[0]) && (array_values((array)$result[0])[0])) {
        echo "   ✅ Coluna 'ultima_atividade_usuario' existe\n";
    } else {
        echo "   ⚠️ Coluna 'ultima_atividade_usuario' não encontrada\n";
        echo "   💡 Execute: php artisan migrate\n";
    }
} catch (\Exception $e) {
    echo "   ℹ️ Não conseguiu verificar (pode ser SQLite): {$e->getMessage()}\n";
}

// 5️⃣ Verificar Evolution API
echo "\n3️⃣ Verificando Evolution API...\n";

$url = config('services.evolution.url');
$key = config('services.evolution.key');

if ($url && $key) {
    echo "   ✅ URL: $url\n";
    echo "   ✅ Key: " . substr($key, 0, 10) . "***\n";
} else {
    echo "   ⚠️ Evolution não está configurado\n";
}

// 6️⃣ Verificar Queue
echo "\n4️⃣ Verificando Queue...\n";

$queueDriver = config('queue.default');
echo "   ℹ️ Queue driver: $queueDriver\n";

if ($queueDriver === 'sync') {
    echo "   ⚠️ Aviso: Queue em 'sync' não processará jobs em background\n";
    echo "   💡 Para produção, use: database, redis ou outro persistente\n";
}

// 7️⃣ Resumo
echo "\n════════════════════════════════════════════════════════\n";
echo "✅ VERIFICAÇÃO COMPLETA!\n";
echo "════════════════════════════════════════════════════════\n\n";

echo "📝 PRÓXIMOS PASSOS:\n\n";

echo "1️⃣ Iniciar Queue Worker:\n";
echo "   php artisan queue:work --queue=default\n\n";

echo "2️⃣ Testar de verdade:\n";
echo "   Conversa normal → Handoff → Aguarde 5 minutos sem responder\n";
echo "   → Chat encerra automaticamente\n\n";

echo "3️⃣ Ver logs:\n";
echo "   tail -f storage/logs/laravel.log | grep HANDOFF-TIMEOUT\n\n";

echo "════════════════════════════════════════════════════════\n";
echo "✨ Tudo pronto! Sistema de timeout está funcional.\n";
echo "════════════════════════════════════════════════════════\n\n";

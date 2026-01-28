#!/usr/bin/env php
<?php

/**
 * 🧪 TESTE DO HANDOFF AUTOMÁTICO - LUCAS
 * Simula o envio de mensagem de handoff e verifica se o job foi agendado
 */

use App\Jobs\SendHumanHandoffMessage;
use Illuminate\Support\Carbon;

// Carregar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     🧪 TESTE - HANDOFF AUTOMÁTICO LUCAS              ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Dados de teste
$clienteId = '553199380844'; // Lucas Prado
$instance = 'N8n';
$threadId = 'thread_test_' . time();

echo "📋 CONFIGURAÇÃO DO TESTE:\n";
echo "   Cliente: $clienteId\n";
echo "   Instância: $instance\n";
echo "   Thread: $threadId\n";
echo "   Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================================
// TESTE 1: Agendar o Job
// ============================================================
echo "🚀 TESTE 1: Agendar Job\n";
echo str_repeat("─", 55) . "\n";

try {
    $delay = Carbon::now()->addMinutes(2);
    
    SendHumanHandoffMessage::dispatch($clienteId, $instance, $threadId)
        ->delay($delay);

    echo "✅ Job agendado com sucesso!\n";
    echo "   Será executado em: " . $delay->format('Y-m-d H:i:s') . "\n";
    echo "   (em aproximadamente 2 minutos)\n\n";

} catch (\Exception $e) {
    echo "❌ Erro ao agendar job:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============================================================
// TESTE 2: Verificar fila
// ============================================================
echo "📊 TESTE 2: Verificar Fila\n";
echo str_repeat("─", 55) . "\n";

try {
    $database = \Illuminate\Support\Facades\DB::class;
    $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')
        ->where('available_at', '>', now())
        ->count();

    echo "Jobs agendados (futuros): $pendingJobs\n";

    // Listar últimos jobs
    $jobs = \Illuminate\Support\Facades\DB::table('jobs')
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get();

    echo "\nÚltimos 3 jobs na fila:\n";
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $display_name = $payload['displayName'] ?? 'Unknown';
        $available_at = Carbon::createFromTimestamp($job->available_at)->format('Y-m-d H:i:s');
        echo "   - $display_name (executa às $available_at)\n";
    }

} catch (\Exception $e) {
    echo "⚠️  Erro ao verificar fila: " . $e->getMessage() . "\n";
}

// ============================================================
// TESTE 3: Instruções para Execução
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║        ⚡ PRÓXIMOS PASSOS PARA TESTAR                ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  CERTIFIQUE-SE QUE QUEUE WORKER ESTÁ RODANDO:\n";
echo "   Terminal 1: php artisan queue:work\n\n";

echo "2️⃣  VOCÊ JÁ AGENDOU O JOB!\n";
echo "   Ele será executado em 2 minutos\n\n";

echo "3️⃣  MONITORAR A EXECUÇÃO:\n";
echo "   Terminal 2: tail -f storage/logs/laravel.log | grep HANDOFF\n\n";

echo "4️⃣  ESPERADO:\n";
echo "   ✅ [HANDOFF] Agendando mensagem de Lucas para 2 minutos\n";
echo "   ✅ [HANDOFF] Iniciando mensagem de Lucas após 2 minutos\n";
echo "   ✅ [HANDOFF] Mensagem de Lucas enviada com sucesso\n\n";

echo "5️⃣  SE QUISER TESTAR COM WEBHOOKS REAIS:\n";
echo "   Mande uma mensagem no WhatsApp para o bot\n";
echo "   Quando o bot responder com \"Vou te conectar a um corretor...\"\n";
echo "   O job será automaticamente agendado\n\n";

echo "6️⃣  VERIFICAR JOBS FALHADOS:\n";
echo "   php artisan queue:failed\n";
echo "   php artisan queue:retry all (se algum falhar)\n\n";

// ============================================================
// RESUMO
// ============================================================
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║              ✅ TESTE CONCLUÍDO                       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "Próximo evento esperado:\n";
echo "   " . $delay->format('Y-m-d H:i:s') . "\n";
echo "   Cliente " . $clienteId . " receberá:\n";
echo "   \"Meu nome é Lucas e darei continuidade ao seu atendimento.\n";
echo "    Como posso ajudá-lo?\"\n\n";

?>

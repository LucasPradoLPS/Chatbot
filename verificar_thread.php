<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$numero = $argv[1] ?? null;
if (!$numero) {
    echo "Uso: php verificar_thread.php [numero]\n";
    exit(1);
}

$thread = \App\Models\Thread::where('numero_cliente', $numero)->first();

if ($thread) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  THREAD ENCONTRADA\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Thread ID: {$thread->thread_id}\n";
    echo "Saudação detectada: " . ($thread->saudacao_inicial ?? 'NENHUMA') . "\n";
    echo "Estado atual: {$thread->estado_atual}\n";
    echo "Etapa fluxo: {$thread->etapa_fluxo}\n";
    echo "Intent: {$thread->intent}\n";
    echo "Criada em: {$thread->created_at}\n\n";
    
    if ($thread->saudacao_inicial) {
        echo "🎉 SUCESSO! Saudação '{$thread->saudacao_inicial}' foi detectada e salva!\n";
    } else {
        echo "⚠️  Nenhuma saudação foi detectada.\n";
    }
    echo "═══════════════════════════════════════════════════════════\n";
} else {
    echo "⚠️  Thread não encontrada para o número: {$numero}\n";
}

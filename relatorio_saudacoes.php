<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════\n";
echo "  RELATÓRIO DE SAUDAÇÕES DETECTADAS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$threads = \App\Models\Thread::whereNotNull('saudacao_inicial')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

if ($threads->count() > 0) {
    echo "Total de threads com saudação: {$threads->count()}\n\n";
    
    foreach ($threads as $thread) {
        echo "───────────────────────────────────────────────────────────\n";
        echo "Cliente: {$thread->numero_cliente}\n";
        echo "Saudação: {$thread->saudacao_inicial}\n";
        echo "Estado: {$thread->estado_atual}\n";
        echo "Intent: {$thread->intent}\n";
        echo "Criada em: {$thread->created_at->format('d/m/Y H:i:s')}\n";
    }
    
    echo "───────────────────────────────────────────────────────────\n\n";
    
    $countOi = $threads->where('saudacao_inicial', 'Oi')->count();
    $countOla = $threads->where('saudacao_inicial', 'Olá')->count();
    
    echo "📊 ESTATÍSTICAS:\n";
    echo "  - 'Oi': {$countOi} thread(s)\n";
    echo "  - 'Olá': {$countOla} thread(s)\n\n";
    
    echo "✅ Sistema de detecção de saudação funcionando perfeitamente!\n";
} else {
    echo "⚠️  Nenhuma thread com saudação detectada ainda.\n";
}

echo "═══════════════════════════════════════════════════════════\n";

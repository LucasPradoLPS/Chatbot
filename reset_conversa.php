#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Initialize Illuminate Database
$app->make('Illuminate\Database\DatabaseManager');

use App\Models\Thread;

$numero = '5531999380844';

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 Procurando thread...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$thread = Thread::where('numero_cliente', $numero)->first();

if ($thread) {
    echo "✅ Thread encontrada!\n";
    echo "   Número: {$thread->numero_cliente}\n";
    echo "   Etapa atual: {$thread->etapa_fluxo}\n";
    echo "   Objetivo: " . ($thread->objetivo ?? 'null') . "\n";
    echo "   LGPD: " . ($thread->lgpd_consentimento ? 'SIM' : 'NÃO') . "\n\n";
    
    echo "🔄 Resetando...\n\n";
    
    $thread->update([
        'etapa_fluxo' => 'boas_vindas',
        'objetivo' => null,
        'lgpd_consentimento' => false,
        'slots' => json_encode([]),
        'intent' => 'indefinido',
        'estado_atual' => 'STATE_START',
    ]);
    
    echo "✅ PRONTO!\n\n";
    echo "Estado novo:\n";
    echo "   Etapa: boas_vindas\n";
    echo "   Objetivo: null\n";
    echo "   LGPD: NÃO\n";
    echo "   Slots: {}\n\n";
    echo "👉 Mande 'Olá' para o bot agora!\n\n";
} else {
    echo "❌ Nenhuma thread encontrada para: $numero\n\n";
    echo "Threads existentes:\n";
    $threads = Thread::limit(10)->get();
    foreach ($threads as $t) {
        echo "   - {$t->numero_cliente} | Etapa: {$t->etapa_fluxo}\n";
    }
    echo "\n";
}
?>

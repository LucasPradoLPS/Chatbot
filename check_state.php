<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$thread = \App\Models\Thread::where('numero_cliente', '553199380844')->first();
if ($thread) {
    echo "✅ Thread encontrada:\n";
    echo "   Estado: " . $thread->estado_atual . "\n";
    echo "   Etapa: " . $thread->etapa_fluxo . "\n";
    echo "   Thread ID: " . $thread->thread_id . "\n";
    echo "   Última atividade: " . $thread->ultima_atividade_usuario . "\n";
} else {
    echo "❌ Thread não encontrada\n";
}

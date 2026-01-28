<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = \Illuminate\Http\Request::capture());

use App\Models\Thread;

$thread = Thread::where('numero_cliente', '553199380844')->first();

if ($thread) {
    echo "Thread encontrada:\n";
    echo "  Thread ID: {$thread->thread_id}\n";
    echo "  Empresa ID: {$thread->empresa_id}\n";
    echo "  Assistente ID: {$thread->assistente_id}\n";
    echo "  Criado: {$thread->created_at}\n";
} else {
    echo "Thread não encontrada para 553199380844\n";
}

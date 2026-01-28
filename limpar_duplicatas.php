<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = $app->make('db');

echo "🧹 Limpando dados duplicados...\n\n";

// Deletar instâncias N8n duplicadas (manter apenas a última)
$instancias = $db->table('instancia_whatsapps')
    ->where('instance_name', 'N8n')
    ->orderBy('id', 'desc')
    ->get();

if ($instancias->count() > 1) {
    echo "Encontradas " . $instancias->count() . " instâncias N8n\n";
    echo "Deletando as antigas...\n\n";
    
    foreach ($instancias->skip(1) as $inst) {
        $db->table('instancia_whatsapps')
            ->where('id', $inst->id)
            ->delete();
        echo "   ✅ Deletada instância ID: {$inst->id}\n";
    }
}

// Limpar threads antigas
$db->table('threads')->truncate();
echo "✅ Threads limpas\n";

// Listar instâncias disponíveis
echo "\n📋 Instâncias agora disponíveis:\n";
$all = $db->table('instancia_whatsapps')->get();
foreach ($all as $inst) {
    echo "   - {$inst->instance_name} (Empresa ID: {$inst->empresa_id})\n";
}

echo "\n✅ Pronto! Agora teste enviando uma mensagem.\n";

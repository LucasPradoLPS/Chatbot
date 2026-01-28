<?php

/**
 * Reset seguro do banco de dados
 * Remove tudo e recria a estrutura
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = $app->make('db');

echo "🔥 RESET SEGURO DO BANCO DE DADOS\n\n";

// 1. Deletar todos os dados (sem dropar tabelas)
echo "1️⃣  Limpando dados das tabelas...\n";

$tables = [
    'threads',
    'mensagens_memoria',
    'agente_gerados',
    'agentes',
    'instancia_whatsapps',
    'empresas',
];

foreach ($tables as $table) {
    try {
        if (DB::schema()->hasTable($table)) {
            DB::table($table)->truncate();
            echo "   ✅ $table limpa\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠️  $table erro: " . $e->getMessage() . "\n";
    }
}

echo "\n2️⃣  Executando migrações...\n";

// 2. Executar todas as migrações
try {
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    
    // Reset de forma segura
    $kernel->call('migrate', ['--force' => true]);
    
    echo "   ✅ Migrações completas\n";
} catch (\Exception $e) {
    echo "   ❌ Erro nas migrações: " . $e->getMessage() . "\n";
}

echo "\n3️⃣  Reseedando dados iniciais...\n";

// 3. Seeds (se houver)
try {
    $kernel->call('db:seed', ['--force' => true]);
    echo "   ✅ Seeds completas\n";
} catch (\Exception $e) {
    echo "   ⚠️  Sem seeds ou erro: " . $e->getMessage() . "\n";
}

echo "\n✅ RESET CONCLUÍDO!\n";
echo "   Próximo passo: Envie uma mensagem de teste\n\n";

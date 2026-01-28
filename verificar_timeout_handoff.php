<?php

/**
 * 🔍 VERIFICAÇÃO - TIMEOUT HANDOFF
 * 
 * Script rápido para verificar se a funcionalidade de timeout está OK
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     🔍 VERIFICAÇÃO - TIMEOUT HANDOFF                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$checks = [];

// ✅ Check 1: Arquivo do Job existe
echo "1️⃣ Verificando arquivo do job...\n";
$jobFile = base_path('app/Jobs/CheckHandoffInactivity.php');
if (file_exists($jobFile)) {
    $checks[] = ['Check 1', 'Job existe', '✅'];
    echo "   ✅ app/Jobs/CheckHandoffInactivity.php encontrado\n\n";
} else {
    $checks[] = ['Check 1', 'Job existe', '❌'];
    echo "   ❌ Job não encontrado!\n\n";
}

// ✅ Check 2: ProcessWhatsappMessage modificado
echo "2️⃣ Verificando modificação no ProcessWhatsappMessage...\n";
$processFile = base_path('app/Jobs/ProcessWhatsappMessage.php');
$content = file_get_contents($processFile);
if (strpos($content, 'CheckHandoffInactivity::dispatch') !== false) {
    $checks[] = ['Check 2', 'ProcessWhatsappMessage modificado', '✅'];
    echo "   ✅ CheckHandoffInactivity está sendo disparado\n\n";
} else {
    $checks[] = ['Check 2', 'ProcessWhatsappMessage modificado', '❌'];
    echo "   ❌ Modificação não encontrada!\n\n";
}

// ✅ Check 3: Database table exists
echo "3️⃣ Verificando tabela 'threads' no banco...\n";
try {
    $threadCount = DB::table('threads')->count();
    $checks[] = ['Check 3', 'Tabela threads existe', '✅'];
    echo "   ✅ Tabela 'threads' encontrada ($threadCount registros)\n\n";
} catch (\Exception $e) {
    $checks[] = ['Check 3', 'Tabela threads existe', '❌'];
    echo "   ❌ Erro: {$e->getMessage()}\n\n";
}

// ✅ Check 4: Evolution API configurada
echo "4️⃣ Verificando configuração Evolution API...\n";
$evolutionUrl = config('services.evolution.url');
$evolutionKey = config('services.evolution.key');

if ($evolutionUrl && $evolutionKey) {
    $checks[] = ['Check 4', 'Evolution API configurada', '✅'];
    echo "   ✅ URL: {$evolutionUrl}\n";
    echo "   ✅ Key: " . substr($evolutionKey, 0, 10) . "***\n\n";
} else {
    $checks[] = ['Check 4', 'Evolution API configurada', '❌'];
    echo "   ❌ Evolution não está configurada no .env\n\n";
}

// ✅ Check 5: Queue driver
echo "5️⃣ Verificando configuração de Queue...\n";
$queueDriver = config('queue.default');
$checks[] = ['Check 5', "Queue driver: $queueDriver", '✅'];
echo "   ✅ Queue driver: $queueDriver\n";
echo "   ℹ️ Certifique-se que 'php artisan queue:work' está rodando\n\n";

// ✅ Check 6: Jobs table exists
echo "6️⃣ Verificando tabela 'jobs' para queue...\n";
try {
    $jobCount = DB::table('jobs')->count();
    $checks[] = ['Check 6', 'Tabela jobs existe', '✅'];
    echo "   ✅ Tabela 'jobs' encontrada ($jobCount jobs pendentes)\n\n";
} catch (\Exception $e) {
    $checks[] = ['Check 6', 'Tabela jobs existe', '⚠️'];
    echo "   ⚠️ Tabela pode não existir (executar: php artisan queue:table)\n\n";
}

// ✅ Check 7: Thread model tem campo ultima_atividade_usuario
echo "7️⃣ Verificando coluna 'ultima_atividade_usuario' na tabela...\n";
try {
    $columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='threads'");
    $hasColumn = collect($columns)->pluck('COLUMN_NAME')->contains('ultima_atividade_usuario');
    
    if ($hasColumn) {
        $checks[] = ['Check 7', 'Coluna ultima_atividade_usuario existe', '✅'];
        echo "   ✅ Coluna existe\n\n";
    } else {
        $checks[] = ['Check 7', 'Coluna ultima_atividade_usuario existe', '❌'];
        echo "   ❌ Coluna não encontrada!\n\n";
    }
} catch (\Exception $e) {
    // Fallback para SQLite
    try {
        $columns = DB::select("PRAGMA table_info(threads)");
        $hasColumn = collect($columns)->pluck('name')->contains('ultima_atividade_usuario');
        
        if ($hasColumn) {
            $checks[] = ['Check 7', 'Coluna ultima_atividade_usuario existe', '✅'];
            echo "   ✅ Coluna existe\n\n";
        } else {
            $checks[] = ['Check 7', 'Coluna ultima_atividade_usuario existe', '❌'];
            echo "   ❌ Coluna não encontrada!\n\n";
        }
    } catch (\Exception $e2) {
        $checks[] = ['Check 7', 'Coluna ultima_atividade_usuario existe', '⚠️'];
        echo "   ⚠️ Não foi possível verificar: {$e2->getMessage()}\n\n";
    }
}

// 📊 Resumo
echo "════════════════════════════════════════════════════════\n";
echo "📊 RESUMO DOS TESTES\n";
echo "════════════════════════════════════════════════════════\n\n";

$table = [
    ['Verificação', 'Status', 'Resultado']
];

foreach ($checks as $check) {
    $table[] = $check;
}

foreach ($table as $row) {
    printf("%-50s %-20s %s\n", $row[0], $row[1], $row[2]);
}

echo "\n";

$passed = count(array_filter($checks, fn($c) => $c[2] === '✅'));
$total = count($checks);

if ($passed === $total) {
    echo "🎉 TODOS OS TESTES PASSARAM! Sistema está pronto!\n\n";
} else {
    echo "⚠️ Alguns testes falharam. Verifique os erros acima.\n\n";
}

echo "════════════════════════════════════════════════════════\n\n";

// 📝 Próximos passos
echo "📝 PRÓXIMOS PASSOS:\n\n";
echo "1️⃣ Iniciar o queue worker:\n";
echo "   php artisan queue:work --queue=default\n\n";

echo "2️⃣ Testar com o script de teste:\n";
echo "   php teste_handoff_timeout.php\n\n";

echo "3️⃣ Acompanhar logs:\n";
echo "   tail -f storage/logs/laravel.log | grep HANDOFF-TIMEOUT\n\n";

echo "4️⃣ Ler documentação completa:\n";
echo "   TIMEOUT_HANDOFF_5_MINUTOS.md\n\n";

echo "════════════════════════════════════════════════════════\n\n";

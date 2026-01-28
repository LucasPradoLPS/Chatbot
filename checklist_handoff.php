#!/usr/bin/env php
<?php

/**
 * ✅ CHECKLIST DE VERIFICAÇÃO - HANDOFF LUCAS
 * Verifica se tudo está configurado corretamente
 */

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     ✅ CHECKLIST - HANDOFF AUTOMÁTICO LUCAS           ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$checks = [];

// ============================================================
// 1. Arquivos Existem
// ============================================================
echo "📁 VERIFICANDO ARQUIVOS:\n";
echo str_repeat("─", 55) . "\n";

$files = [
    'app/Jobs/SendHumanHandoffMessage.php' => 'Job para enviar mensagem de Lucas',
    'app/Jobs/ProcessWhatsappMessage.php' => 'Job principal (modificado)',
    '.env' => 'Configuração de ambiente',
];

foreach ($files as $file => $desc) {
    $exists = file_exists($file);
    $icon = $exists ? '✅' : '❌';
    echo "$icon $file - $desc\n";
    $checks[$file] = $exists;
}

// ============================================================
// 2. Configuração do .env
// ============================================================
echo "\n🔧 VERIFICANDO CONFIGURAÇÃO .ENV:\n";
echo str_repeat("─", 55) . "\n";

if (file_exists('.env')) {
    $env = [];
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }

    $configs = [
        'QUEUE_CONNECTION' => 'database',
        'EVOLUTION_URL' => 'http://localhost:8080',
        'EVOLUTION_KEY' => 'VnbFQWPgYUBaLyjXNhJCfQ83WtHZWrHq',
    ];

    foreach ($configs as $key => $expectedStart) {
        $value = $env[$key] ?? 'NÃO ENCONTRADO';
        $ok = !empty($value) && strpos((string)$value, '*') === false;
        $icon = $ok ? '✅' : '⚠️';
        echo "$icon $key: " . (strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value) . "\n";
        $checks[$key] = $ok;
    }
} else {
    echo "❌ Arquivo .env não encontrado\n";
    $checks['.env'] = false;
}

// ============================================================
// 3. Banco de Dados
// ============================================================
echo "\n💾 VERIFICANDO BANCO DE DADOS:\n";
echo str_repeat("─", 55) . "\n";

try {
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    // Verificar tabela 'jobs'
    $hasJobsTable = \Illuminate\Support\Facades\Schema::hasTable('jobs');
    echo ($hasJobsTable ? '✅' : '❌') . " Tabela 'jobs': " . ($hasJobsTable ? 'existe' : 'não existe') . "\n";
    $checks['tabela_jobs'] = $hasJobsTable;

    // Contar jobs agendados
    if ($hasJobsTable) {
        $jobsCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
        $pendingCount = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('available_at', '>', time())
            ->count();
        echo "📊 Jobs na fila: $jobsCount total, $pendingCount agendados\n";
    }

    // Verificar coluna 'payload'
    $hasPayloadColumn = \Illuminate\Support\Facades\DB::connection()
        ->getSchemaBuilder()
        ->hasColumn('jobs', 'payload');
    echo ($hasPayloadColumn ? '✅' : '❌') . " Coluna 'payload': " . ($hasPayloadColumn ? 'existe' : 'não existe') . "\n";
    $checks['coluna_payload'] = $hasPayloadColumn;

} catch (\Exception $e) {
    echo "⚠️  Erro ao conectar ao banco: " . $e->getMessage() . "\n";
    $checks['banco_dados'] = false;
}

// ============================================================
// 4. Sintaxe PHP
// ============================================================
echo "\n🔍 VERIFICANDO SINTAXE PHP:\n";
echo str_repeat("─", 55) . "\n";

$phpFiles = [
    'app/Jobs/SendHumanHandoffMessage.php',
    'app/Jobs/ProcessWhatsappMessage.php',
];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $result = shell_exec("php -l '$file' 2>&1");
        $isValid = strpos($result, 'No syntax errors') !== false;
        $icon = $isValid ? '✅' : '❌';
        echo "$icon $file\n";
        $checks[$file . '_syntax'] = $isValid;
    }
}

// ============================================================
// 5. Dependências
// ============================================================
echo "\n📦 VERIFICANDO DEPENDÊNCIAS:\n";
echo str_repeat("─", 55) . "\n";

$dependencies = [
    'Illuminate\Bus\Queueable' => 'Laravel Queue',
    'Illuminate\Support\Facades\Http' => 'Laravel HTTP Client',
    'Illuminate\Support\Facades\Log' => 'Laravel Logging',
];

foreach ($dependencies as $class => $name) {
    $exists = class_exists($class) || interface_exists($class);
    $icon = $exists ? '✅' : '❌';
    echo "$icon $name\n";
    $checks[$name] = $exists;
}

// ============================================================
// 6. Funções Requeridas
// ============================================================
echo "\n⚙️  VERIFICANDO FUNÇÕES:\n";
echo str_repeat("─", 55) . "\n";

$functions = [
    'curl_init' => 'cURL para requisições HTTP',
    'json_encode' => 'JSON encoding',
    'strpos' => 'String operations',
];

foreach ($functions as $func => $desc) {
    $exists = function_exists($func);
    $icon = $exists ? '✅' : '❌';
    echo "$icon $func - $desc\n";
    $checks[$func] = $exists;
}

// ============================================================
// RESUMO
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║                   📊 RESUMO                           ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$total = count($checks);
$passed = count(array_filter($checks));
$percentage = ($passed / $total) * 100;

echo "Verificações: $passed/$total (" . round($percentage) . "%)\n\n";

if ($percentage === 100) {
    echo "✅ TUDO OK! Você está pronto para usar o handoff automático!\n\n";
    echo "Próximo passo:\n";
    echo "1. Execute: php artisan queue:work\n";
    echo "2. Mande uma mensagem no WhatsApp para o bot\n";
    echo "3. Aguarde 2 minutos após o handoff\n";
    echo "4. Receberá mensagem de Lucas\n\n";
} else {
    echo "⚠️  Alguns problemas encontrados:\n";
    foreach ($checks as $item => $ok) {
        if (!$ok) {
            echo "   ❌ $item\n";
        }
    }
    echo "\nResolva os problemas acima antes de usar.\n\n";
}

echo "Para mais informações, veja:\n";
echo "   - HANDOFF_LUCAS_README.md\n";
echo "   - IMPLEMENTACAO_HANDOFF_LUCAS.md\n\n";

?>

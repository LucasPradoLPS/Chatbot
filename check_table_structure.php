<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Verificar colunas da tabela empresas
$columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'empresas' ORDER BY ordinal_position");

echo "Colunas da tabela 'empresas':\n";
foreach ($columns as $col) {
    echo "  - " . $col->column_name . "\n";
}

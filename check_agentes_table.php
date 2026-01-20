<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Colunas de 'agente_gerados':\n";
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'agente_gerados' ORDER BY ordinal_position");
foreach ($cols as $c) {
    echo "  - " . $c->column_name . "\n";
}

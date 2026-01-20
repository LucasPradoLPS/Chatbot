<?php
require_once 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$agents = \DB::table('agente_gerados')->get();

foreach ($agents as $agent) {
    echo "Cliente: {$agent->numero_cliente}\n";
    echo "Assistant ID: {$agent->assistant_id}\n";
    echo "---\n";
}

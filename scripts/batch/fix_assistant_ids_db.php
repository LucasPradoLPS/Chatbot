<?php
// Quick fix to update assistant IDs in database
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$correctAssistantId = 'asst_TK2zcCJXJE7reRvMIY0Vw4im';

echo "Checking current agents...\n";
$agents = DB::table('agente_gerados')->get();

foreach ($agents as $agent) {
    echo sprintf(
        "ID: %d | Cliente: %s | Funcao: %s | Assistant: %s\n",
        $agent->id,
        $agent->numero_cliente ?? 'NULL',
        $agent->funcao,
        $agent->agente_base_id
    );
}

echo "\nUpdating assistant IDs...\n";

$updated = DB::table('agente_gerados')
    ->where(function ($query) {
        $query->where('agente_base_id', '2')
            ->orWhereNull('agente_base_id')
            ->orWhere('agente_base_id', '');
    })
    ->update(['agente_base_id' => $correctAssistantId]);

echo "Updated: $updated records\n";

echo "\nVerifying update...\n";
$agentsAfter = DB::table('agente_gerados')->get();

foreach ($agentsAfter as $agent) {
    echo sprintf(
        "ID: %d | Cliente: %s | Funcao: %s | Assistant: %s\n",
        $agent->id,
        $agent->numero_cliente ?? 'NULL',
        $agent->funcao,
        $agent->agente_base_id
    );
}

echo "\nDone!\n";

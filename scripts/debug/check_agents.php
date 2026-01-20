<?php

require 'vendor/autoload.php';
define('LARAVEL_START', microtime(true));
$app = require 'bootstrap/app.php';

use App\Models\AgenteGerado;

$agents = AgenteGerado::orderBy('created_at', 'desc')->limit(5)->get();

foreach ($agents as $agent) {
    echo "ID: {$agent->id}, Empresa: {$agent->empresa_id}, Agente Base: {$agent->agente_base_id}, Created: {$agent->created_at}\n";
}

?>

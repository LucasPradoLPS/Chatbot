<?php

require 'vendor/autoload.php';

use App\Models\AgenteGerado;

// This requires proper Laravel initialization
if (file_exists('bootstrap/app.php')) {
    $app = require 'bootstrap/app.php';
    $kernel = $app->make('Illuminate\Foundation\Http\Kernel');
    
    // Get the last 3 agentes gerados
    $agents = AgenteGerado::orderBy('id', 'desc')->limit(3)->get(['id', 'empresa_id', 'agente_base_id', 'created_at']);
    
    echo "=== Recent Agentes Gerados ===\n";
    foreach ($agents as $agent) {
        echo sprintf("ID: %d | Empresa: %d | Assistant: %s | Created: %s\n", 
            $agent->id, 
            $agent->empresa_id, 
            $agent->agente_base_id, 
            $agent->created_at
        );
    }
} else {
    echo "Bootstrap file not found\n";
}

?>

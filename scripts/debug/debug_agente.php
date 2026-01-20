<?php
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$empresa_id = 2;

echo "Buscando agente para empresa $empresa_id...\n";

$agente = \App\Models\Agente::where('empresa_id', $empresa_id)->first();

echo "Resultado: ";
if ($agente) {
    echo "✓ Agente encontrado\n";
    echo "  - ID: " . $agente->id . "\n";
    echo "  - IA Ativa: " . ($agente->ia_ativa ? 'SIM' : 'NÃO') . "\n";
    echo "  - Responder Grupo: " . ($agente->responder_grupo ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "✗ Nenhum agente encontrado\n";
    
    // Listar todos os agentes
    echo "\nTodos os agentes no banco:\n";
    $todos = \App\Models\Agente::all();
    foreach ($todos as $ag) {
        echo "  - ID: " . $ag->id . ", Empresa: " . $ag->empresa_id . ", IA: " . ($ag->ia_ativa ? 'SIM' : 'NÃO') . "\n";
    }
}
?>

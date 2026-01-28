<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = \Illuminate\Http\Request::capture());

use App\Models\AgenteGerado;
use App\Models\Empresa;

echo "=== VERIFICANDO ASSISTENTES ===\n\n";

$empresas = Empresa::all();
foreach ($empresas as $empresa) {
    echo "Empresa: {$empresa->id} - {$empresa->nome_empresa}\n";
    
    $agentes = AgenteGerado::where('empresa_id', $empresa->id)->get();
    if ($agentes->count() === 0) {
        echo "  ❌ Nenhum agente gerado!\n";
    } else {
        foreach ($agentes as $agente) {
            echo "  ✅ Assistant ID: {$agente->assistant_id}\n";
            echo "     Nome: {$agente->nome_agente}\n";
            echo "     Criado: {$agente->created_at}\n";
        }
    }
    echo "\n";
}

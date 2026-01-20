<?php
// Atualizar ou criar empresa "california" no banco de dados
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$empresaNome = 'california';
$correctAssistantId = 'asst_TK2zcCJXJE7reRvMIY0Vw4im';

echo "Atualizando empresa para: $empresaNome\n\n";

// Verificar se empresa existe
$empresa = DB::table('empresas')->where('nome', $empresaNome)->first();

if ($empresa) {
    echo "Empresa '$empresaNome' encontrada (ID: {$empresa->id})\n";
    $empresaId = $empresa->id;
} else {
    echo "Empresa '$empresaNome' não encontrada. Criando...\n";
    $empresaId = DB::table('empresas')->insertGetId([
        'nome' => $empresaNome,
        'memoria_limite' => 10000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Empresa criada com ID: $empresaId\n";
}

echo "\n--- AGENTES DA EMPRESA ---\n";

// Listar agentes da empresa
$agentes = DB::table('agente_gerados')
    ->where('empresa_id', $empresaId)
    ->get();

if ($agentes->isEmpty()) {
    echo "Nenhum agente encontrado. Criando agente padrão...\n";
    
    DB::table('agente_gerados')->insert([
        'empresa_id' => $empresaId,
        'funcao' => 'atendente_ia',
        'agente_base_id' => $correctAssistantId,
        'assistant_id' => $correctAssistantId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "Agente criado com sucesso!\n";
} else {
    echo "Agentes encontrados:\n";
    foreach ($agentes as $agente) {
        echo "  ID: {$agente->id} | Função: {$agente->funcao} | Assistant: {$agente->agente_base_id}\n";
    }
}

// Atualizar todos os agentes da empresa com o assistant correto
$updated = DB::table('agente_gerados')
    ->where('empresa_id', $empresaId)
    ->where('funcao', 'atendente_ia')
    ->update(['agente_base_id' => $correctAssistantId]);

echo "\nAgentes atualizados: $updated\n";

echo "\n--- CONFIGURAÇÃO FINAL ---\n";
echo "Empresa: $empresaNome (ID: $empresaId)\n";
echo "Assistant ID: $correctAssistantId\n";
echo "Status: ✅ PRONTO PARA USAR\n";

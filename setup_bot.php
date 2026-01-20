<?php
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Encontrar a empresa (deve ser a ID 2 conforme o erro)
$empresa = \App\Models\Empresa::find(2);

if (!$empresa) {
    echo "Criando empresa padrão...\n";
    $empresa = \App\Models\Empresa::create([
        'nome' => 'Default',
        'memoria_limite' => 10000,
    ]);
}

echo "✓ Empresa: " . $empresa->nome . " (ID: " . $empresa->id . ")\n";

// Criar agente padrão
$agente = \App\Models\Agente::where('empresa_id', $empresa->id)->first();

if (!$agente) {
    echo "Criando agente padrão...\n";
    $agente = \App\Models\Agente::create([
        'nome' => 'ChatBot Padrão',
        'empresa_id' => $empresa->id,
        'prompt' => 'Você é um assistente de atendimento ao cliente amigável e profissional. Responda sempre de forma clara e concisa.',
        'ativo' => true,
    ]);
    echo "✓ Agente criado: " . $agente->nome . "\n";
} else {
    echo "✓ Agente já existe: " . $agente->nome . "\n";
}

// Verificar se há property que controla a IA
$property = \App\Models\Property::where('empresa_id', $empresa->id)
    ->where('property_key', 'ia_ativa')
    ->first();

if (!$property) {
    echo "Ativando IA...\n";
    \App\Models\Property::create([
        'empresa_id' => $empresa->id,
        'property_key' => 'ia_ativa',
        'property_value' => '1',
    ]);
    echo "✓ IA ativada\n";
} else {
    echo "✓ IA já está configurada\n";
}

echo "\n✅ Configuração concluída!\n";
?>

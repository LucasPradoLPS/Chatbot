<?php
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$openai_key = config('services.openai.key');
$empresa_id = 2;

echo "=== Criando Assistente OpenAI para empresa $empresa_id ===\n\n";

// 1. Verificar se já existe assistente
$agenteGerado = \App\Models\AgenteGerado::where('empresa_id', $empresa_id)->first();

if ($agenteGerado && $agenteGerado->assistant_id) {
    echo "✓ Assistente já existe: " . $agenteGerado->assistant_id . "\n";
    exit(0);
}

// 2. Criar novo assistente
echo "Criando novo assistente...\n";

$response = Http::withToken($openai_key)
    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
    ->post('https://api.openai.com/v1/assistants', [
        'name' => 'Bot Whatsapp - Empresa ' . $empresa_id,
        'description' => 'Assistente de atendimento ao cliente via WhatsApp',
        'model' => 'gpt-4o-mini',
        'instructions' => 'Você é um assistente de atendimento ao cliente amigável e profissional. Responda sempre de forma clara, concisa e em português. Ajude os clientes com suas dúvidas e necessidades.',
        'tools' => [
            [
                'type' => 'code_interpreter'
            ]
        ]
    ]);

if ($response->failed()) {
    echo "✗ Erro ao criar assistente:\n";
    echo $response->body() . "\n";
    exit(1);
}

$data = $response->json();
$assistant_id = $data['id'] ?? null;

if (!$assistant_id) {
    echo "✗ Assistente não retornou ID\n";
    echo print_r($data, true);
    exit(1);
}

echo "✓ Assistente criado: " . $assistant_id . "\n";

// 3. Registrar no banco
if ($agenteGerado) {
    $agenteGerado->update(['assistant_id' => $assistant_id]);
    echo "✓ AgenteGerado atualizado\n";
} else {
    // Buscar agente normal
    $agente = \App\Models\Agente::where('empresa_id', $empresa_id)->first();
    
    if (!$agente) {
        echo "✗ Nenhum agente encontrado para empresa $empresa_id\n";
        exit(1);
    }
    
    \App\Models\AgenteGerado::create([
        'empresa_id' => $empresa_id,
        'agente_base_id' => $agente->id,
        'funcao' => 'atendente_ia',
        'assistant_id' => $assistant_id,
    ]);
    echo "✓ AgenteGerado criado\n";
}

echo "\n✅ Assistente configurado com sucesso!\n";
?>

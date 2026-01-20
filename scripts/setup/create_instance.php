<?php
// Script para criar instância no banco
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar e criar empresa padrão
$empresa = \App\Models\Empresa::firstOrCreate(
    ['nome' => 'Default'],
    ['nome' => 'Default']
);
echo "✓ Empresa: " . $empresa->nome . "\n";

// Verificar e criar a instância N8n
$instancia = \App\Models\InstanciaWhatsapp::where('instance_name', 'N8n')->first();

if (!$instancia) {
    echo "Criando instância N8n...\n";
    \App\Models\InstanciaWhatsapp::create([
        'instance_name' => 'N8n',
        'empresa_id' => $empresa->id,
    ]);
    echo "✓ Instância N8n criada com sucesso!\n";
} else {
    echo "✓ Instância N8n já existe\n";
}
?>

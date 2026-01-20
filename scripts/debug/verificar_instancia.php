<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InstanciaWhatsapp;
use App\Models\Empresa;

$instancias = InstanciaWhatsapp::all();

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  VERIFICANDO INSTÂNCIAS WHATSAPP\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($instancias->count() === 0) {
    echo "❌ NENHUMA INSTÂNCIA ENCONTRADA!\n\n";
    
    // Tenta criar uma instância de teste
    echo "📋 Criando instância de teste...\n";
    
    $empresa = Empresa::first();
    if (!$empresa) {
        echo "❌ Nenhuma empresa encontrada. Criando empresa...\n";
        $empresa = Empresa::create([
            'nome' => 'Empresa Teste',
            'cnpj' => '00.000.000/0000-00',
            'email' => 'teste@empresa.com'
        ]);
    }
    
    $instancia = InstanciaWhatsapp::create([
        'numero' => '5511987654321',
        'nome' => 'Bot Teste',
        'empresa_id' => $empresa->id,
        'ativa' => true,
        'chave_evolution' => 'chave-teste',
        'url_evolution' => 'https://evolution.seu-servidor.com'
    ]);
    
    echo "✅ Instância criada! ID: " . $instancia->id . "\n";
    echo "   Número: " . $instancia->numero . "\n";
    echo "   Nome: " . $instancia->nome . "\n";
    echo "   Empresa: " . $empresa->nome . "\n";
} else {
    echo "✅ " . $instancias->count() . " instância(s) encontrada(s):\n\n";
    
    foreach ($instancias as $inst) {
        $empresa = $inst->empresa;
        echo "📱 " . $inst->nome . "\n";
        echo "   Número: " . $inst->numero . "\n";
        echo "   Ativa: " . ($inst->ativa ? 'SIM ✓' : 'NÃO ✗') . "\n";
        echo "   Empresa: " . ($empresa ? $empresa->nome : 'N/A') . "\n";
        echo "   Criada: " . $inst->created_at->format('d/m/Y H:i') . "\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n\n";

<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InstanciaWhatsapp;

// Encontra a instância
$inst = InstanciaWhatsapp::first();

if (!$inst) {
    echo "❌ Nenhuma instância encontrada!\n";
    exit(1);
}

// Atualiza
$inst->instance_name = 'seu_numero_whatsapp';  // Conforme esperado no webhook
$inst->save();

echo "\n✅ INSTÂNCIA ATUALIZADA COM SUCESSO!\n\n";
echo "📱 Configuração:\n";
echo "   Instance Name: " . $inst->instance_name . "\n";
echo "   Empresa: " . ($inst->empresa ? $inst->empresa->nome : 'N/A') . "\n";
echo "   ID: " . $inst->id . "\n\n";

echo "🎯 Agora tente enviar uma imagem novamente!\n\n";

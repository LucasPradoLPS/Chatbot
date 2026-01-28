<?php

/**
 * Limpar dados duplicados do cliente
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = $app->make('db');

echo "🧹 LIMPANDO DADOS DUPLICADOS...\n\n";

// Deletar todas as threads do cliente 553199380844
$deleted = $db->table('threads')
    ->where('numero_cliente', '553199380844')
    ->delete();

echo "✅ Deletadas $deleted threads do cliente 553199380844\n\n";

// Deletar todas as lead_captures do cliente
$deletedLeads = $db->table('lead_captures')
    ->where('numero_cliente', '553199380844')
    ->delete();

echo "✅ Deletadas $deletedLeads lead captures do cliente 553199380844\n\n";

echo "🎉 Pronto! Dados limpos. Agora tente enviar uma nova mensagem!\n";

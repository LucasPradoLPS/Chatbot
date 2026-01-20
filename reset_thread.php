<?php
/**
 * Script para resetar uma thread (conversa) de um cliente
 * Usa seu número WhatsApp para encontrar e resetar
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Thread;

$app = require_once __DIR__ . '/bootstrap/app.php';

// ========== CONFIGURAÇÃO ==========
// Coloque seu número aqui (apenas dígitos, sem @ ou caracteres especiais)
$seu_numero = '5511987654321'; // Exemplo: 5511987654321
// ========== FIM CONFIGURAÇÃO ==========

if (php_sapi_name() !== 'cli') {
    die('[ERRO] Este script deve ser rodado via CLI (terminal)');
}

echo "\n╔════════════════════════════════════════╗\n";
echo "║      RESET DE THREAD (Conversa)        ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// Procurar a thread
$thread = Thread::where('numero_cliente', $seu_numero)
    ->orWhere('numero_cliente', 'like', '%' . substr($seu_numero, -10))
    ->first();

if (!$thread) {
    echo "❌ Nenhuma thread encontrada para o número: $seu_numero\n\n";
    echo "Procure por:\n";
    $threads = Thread::limit(10)->get();
    foreach ($threads as $t) {
        echo "  - {$t->numero_cliente} (etapa: {$t->etapa_fluxo})\n";
    }
    exit(1);
}

echo "✅ Thread encontrada:\n";
echo "   Número: {$thread->numero_cliente}\n";
echo "   Etapa atual: {$thread->etapa_fluxo}\n";
echo "   Objetivo: {$thread->objetivo}\n";
echo "   LGPD consentido: " . ($thread->lgpd_consentimento ? 'SIM' : 'NÃO') . "\n\n";

echo "🔄 Resetando...\n\n";

// Resetar a thread
$thread->update([
    'etapa_fluxo' => 'boas_vindas',
    'objetivo' => null,
    'lgpd_consentimento' => false,
    'slots' => [],
    'intent' => 'indefinido',
    'estado_atual' => 'STATE_START',
]);

// Deletar histórico de mensagens (opcional - descomente se quiser)
// MensagensMemoria::where('thread_id', $thread->thread_id)->delete();

echo "✅ Thread resetada com sucesso!\n\n";
echo "Estado novo:\n";
echo "   Etapa: {$thread->etapa_fluxo}\n";
echo "   Objetivo: {$thread->objetivo}\n";
echo "   LGPD: NÃO\n";
echo "   Slots: vazios\n\n";

echo "👉 Agora mande uma mensagem ao bot!\n\n";
?>

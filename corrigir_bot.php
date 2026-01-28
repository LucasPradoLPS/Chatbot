<?php

/**
 * 🔧 SCRIPT AUTOMÁTICO DE CORREÇÃO
 * Tenta corrigir os problemas identificados
 * 
 * Uso: php corrigir_bot.php
 */

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║      🔧 CORREÇÃO AUTOMÁTICA DO CHATBOT - v1.0         ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$evolutionUrl = env('EVOLUTION_URL');
$evolutionKey = env('EVOLUTION_KEY');

// ============================================================
// 1️⃣ LIMPAR CACHE DO LARAVEL
// ============================================================
echo "🧹 [1/5] Limpando cache do Laravel...\n";
try {
    Artisan::call('cache:clear');
    echo "✅ Cache limpo\n\n";
} catch (\Exception $e) {
    echo "⚠️  Erro ao limpar cache: " . $e->getMessage() . "\n\n";
}

// ============================================================
// 2️⃣ LIMPAR CONFIG
// ============================================================
echo "📝 [2/5] Limpando config...\n";
try {
    Artisan::call('config:clear');
    echo "✅ Config limpa\n\n";
} catch (\Exception $e) {
    echo "⚠️  Erro ao limpar config: " . $e->getMessage() . "\n\n";
}

// ============================================================
// 3️⃣ REINICIAR FILA
// ============================================================
echo "📨 [3/5] Reiniciando fila...\n";
try {
    Artisan::call('queue:restart');
    echo "✅ Fila reiniciada\n\n";
} catch (\Exception $e) {
    echo "⚠️  Erro ao reiniciar fila: " . $e->getMessage() . "\n\n";
}

// ============================================================
// 4️⃣ VERIFICAR E REATIVAR INSTÂNCIA
// ============================================================
echo "🔑 [4/5] Verificando instância N8n...\n";

try {
    $response = Http::timeout(5)
        ->withHeaders(['apikey' => $evolutionKey])
        ->get($evolutionUrl . '/instances');
    
    if ($response->successful()) {
        $data = $response->json();
        $instancias = $data['instances'] ?? [];
        $n8nEncontrada = false;
        
        foreach ($instancias as $inst) {
            $nome = strtolower($inst['instance_name'] ?? $inst['name'] ?? '');
            if ($nome === 'n8n') {
                $n8nEncontrada = true;
                $estado = $inst['state'] ?? 'unknown';
                
                if ($estado === 'open') {
                    echo "✅ Instância N8n está ativa\n\n";
                } else {
                    echo "⚠️  Instância N8n encontrada mas inativa (estado: $estado)\n";
                    echo "📢 Você precisa reativar a instância no painel do Evolution\n";
                    echo "    URL: http://localhost:8080\n\n";
                }
                break;
            }
        }
        
        if (!$n8nEncontrada) {
            echo "❌ Instância N8n não encontrada\n";
            echo "📝 Criando instância N8n...\n";
            
            try {
                $createResponse = Http::timeout(10)
                    ->withHeaders(['apikey' => $evolutionKey])
                    ->post($evolutionUrl . '/instances/create', [
                        'instanceName' => 'N8n',
                        'token' => $evolutionKey,
                    ]);
                
                if ($createResponse->successful()) {
                    echo "✅ Instância N8n criada com sucesso!\n";
                    echo "📱 Próximo passo: Escaneie o QR Code com seu WhatsApp\n\n";
                } else {
                    echo "⚠️  Erro ao criar instância: " . $createResponse->status() . "\n\n";
                }
            } catch (\Exception $e) {
                echo "⚠️  Erro ao criar instância: " . $e->getMessage() . "\n\n";
            }
        }
    } else {
        echo "⚠️  Erro ao verificar instâncias: " . $response->status() . "\n\n";
    }
} catch (\Exception $e) {
    echo "⚠️  Erro ao conectar com Evolution: " . $e->getMessage() . "\n\n";
}

// ============================================================
// 5️⃣ LIMPAR JOBS FALHADOS
// ============================================================
echo "🗑️  [5/5] Limpando jobs falhados...\n";

try {
    $deleted = DB::table('failed_jobs')->delete();
    echo "✅ Deletados $deleted jobs falhados\n\n";
} catch (\Exception $e) {
    echo "⚠️  Erro ao deletar jobs: " . $e->getMessage() . "\n\n";
}

// ============================================================
// PRÓXIMOS PASSOS
// ============================================================
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║              ✅ LIMPEZA CONCLUÍDA                      ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "📋 PRÓXIMOS PASSOS:\n";
echo str_repeat("─", 55) . "\n";
echo "1. Rode o diagnóstico novamente:\n";
echo "   php diagnosticar_bot.php\n\n";

echo "2. Se a instância N8n está inativa, ative-a em:\n";
echo "   http://localhost:8080\n\n";

echo "3. Teste envio de mensagem:\n";
echo "   php artisan bot:send-message\n\n";

echo "4. Monitore os logs:\n";
echo "   tail -f storage/logs/laravel.log\n\n";

exit(0);

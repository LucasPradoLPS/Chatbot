<?php

/**
 * 🔍 SCRIPT DE DIAGNÓSTICO COMPLETO DO CHATBOT
 * Identifica e sugere soluções para problemas de entrega de mensagens
 * 
 * Uso: php diagnosticar_bot.php
 */

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     🔍 DIAGNÓSTICO COMPLETO DO CHATBOT - v1.0        ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$erros = [];
$avisos = [];
$sucessos = [];

// ============================================================
// 1️⃣ VERIFICAR CONFIGURAÇÕES DO .ENV
// ============================================================
echo "📋 [1/7] VERIFICANDO CONFIGURAÇÕES...\n";
echo str_repeat("─", 55) . "\n";

$evolutionUrl = env('EVOLUTION_URL');
$evolutionKey = env('EVOLUTION_KEY');
$openaiKey = env('OPENAI_KEY');

if (!$evolutionUrl || !$evolutionKey) {
    $erros[] = "❌ EVOLUTION_URL ou EVOLUTION_KEY não configurados no .env";
} else {
    $sucessos[] = "✅ Evolution configurado: $evolutionUrl";
}

if (!$openaiKey) {
    $erros[] = "❌ OPENAI_KEY não configurada no .env";
} else {
    $sucessos[] = "✅ OpenAI Key configurada";
}

// ============================================================
// 2️⃣ VERIFICAR CONEXÃO COM EVOLUTION
// ============================================================
echo "\n🌐 [2/7] VERIFICANDO EVOLUTION API...\n";
echo str_repeat("─", 55) . "\n";

$evolutionAlive = false;
try {
    $response = Http::timeout(5)
        ->withHeaders(['apikey' => $evolutionKey])
        ->get($evolutionUrl . '/health');
    
    if ($response->successful()) {
        $sucessos[] = "✅ Evolution API respondendo";
        $evolutionAlive = true;
    } else {
        $erros[] = "❌ Evolution retornou: " . $response->status();
    }
} catch (\Exception $e) {
    $erros[] = "❌ Erro ao conectar com Evolution: " . $e->getMessage();
}

// ============================================================
// 3️⃣ VERIFICAR INSTÂNCIAS NO EVOLUTION
// ============================================================
echo "\n🔑 [3/7] VERIFICANDO INSTÂNCIAS...\n";
echo str_repeat("─", 55) . "\n";

$instancias = [];
$instanciaAtiva = false;

if ($evolutionAlive) {
    try {
        $response = Http::timeout(5)
            ->withHeaders(['apikey' => $evolutionKey])
            ->get($evolutionUrl . '/instances');
        
        if ($response->successful()) {
            $data = $response->json();
            $instancias = $data['instances'] ?? [];
            
            if (!empty($instancias)) {
                $sucessos[] = "✅ Instâncias encontradas: " . count($instancias);
                foreach ($instancias as $inst) {
                    $nome = $inst['instance_name'] ?? $inst['name'] ?? 'desconhecida';
                    $estado = ($inst['state'] ?? 'unknown') === 'open' ? '✅ ATIVA' : '⚠️ INATIVA';
                    echo "   → $nome: $estado\n";
                    
                    if (strtolower($nome) === 'n8n' && $inst['state'] === 'open') {
                        $instanciaAtiva = true;
                    }
                }
                
                if (!$instanciaAtiva) {
                    $avisos[] = "⚠️ Instância 'N8n' não está ativa ou não existe";
                } else {
                    $sucessos[] = "✅ Instância N8n está ativa";
                }
            } else {
                $erros[] = "❌ Nenhuma instância encontrada no Evolution";
            }
        }
    } catch (\Exception $e) {
        $avisos[] = "⚠️ Erro ao listar instâncias: " . $e->getMessage();
    }
}

// ============================================================
// 4️⃣ VERIFICAR DATABASE
// ============================================================
echo "\n💾 [4/7] VERIFICANDO DATABASE...\n";
echo str_repeat("─", 55) . "\n";

try {
    DB::connection()->getPdo();
    $sucessos[] = "✅ Database conectado";
    
    // Verificar tabelas críticas
    $tables = ['threads', 'agentes', 'agentes_gerados', 'empresas'];
    foreach ($tables as $table) {
        try {
            $count = DB::table($table)->count();
            echo "   → $table: $count registros\n";
        } catch (\Exception $e) {
            $avisos[] = "⚠️ Erro ao contar $table";
        }
    }
    
    // Verificar threads ativas
    $threadsAtivas = DB::table('threads')
        ->where('updated_at', '>=', now()->subHours(2))
        ->count();
    echo "   → Threads ativas (últimas 2h): $threadsAtivas\n";
    
    if ($threadsAtivas > 0) {
        $sucessos[] = "✅ Threads ativas encontradas";
    }
    
} catch (\Exception $e) {
    $erros[] = "❌ Erro ao conectar com database: " . $e->getMessage();
}

// ============================================================
// 5️⃣ VERIFICAR FILA (QUEUE)
// ============================================================
echo "\n📨 [5/7] VERIFICANDO FILA...\n";
echo str_repeat("─", 55) . "\n";

$queueConnection = env('QUEUE_CONNECTION');
echo "   Conexão da fila: $queueConnection\n";

if ($queueConnection === 'sync') {
    $avisos[] = "⚠️ Fila está em modo SYNC (síncrono). Recomenda-se usar 'database' para melhor performance";
}

try {
    $jobsPendentes = DB::table('jobs')->count();
    $jobsFalhados = DB::table('failed_jobs')->count();
    
    echo "   → Jobs pendentes: $jobsPendentes\n";
    echo "   → Jobs falhados: $jobsFalhados\n";
    
    if ($jobsFalhados > 0) {
        $avisos[] = "⚠️ Há $jobsFalhados jobs falhados na fila";
    }
} catch (\Exception $e) {
    // Tabela pode não existir
}

// ============================================================
// 6️⃣ VERIFICAR LOGS RECENTES
// ============================================================
echo "\n📊 [6/7] ANALISANDO LOGS...\n";
echo str_repeat("─", 55) . "\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $conteudo = file_get_contents($logFile);
    $linhas = array_reverse(explode("\n", $conteudo));
    
    $statusPending = 0;
    $errosEvolution = 0;
    $mensagensEnviadas = 0;
    
    foreach (array_slice($linhas, 0, 500) as $linha) {
        if (strpos($linha, 'status":"PENDING') !== false) {
            $statusPending++;
        }
        if (strpos($linha, 'Resposta da API Evolution') !== false) {
            $mensagensEnviadas++;
        }
        if (strpos($linha, '[ERROR]') !== false || strpos($linha, 'error') !== false) {
            $errosEvolution++;
        }
    }
    
    echo "   → Mensagens com status PENDING: $statusPending\n";
    echo "   → Mensagens enviadas (últimas 500 linhas): $mensagensEnviadas\n";
    echo "   → Linhas com erro: $errosEvolution\n";
    
    if ($statusPending > 10) {
        $erros[] = "❌ Muitas mensagens com status PENDING ($statusPending). Evolution não está enviando!";
    }
} else {
    $avisos[] = "⚠️ Arquivo de log não encontrado";
}

// ============================================================
// 7️⃣ TESTAR ENVIO DE MENSAGEM
// ============================================================
echo "\n✉️  [7/7] TESTANDO ENVIO DE MENSAGEM...\n";
echo str_repeat("─", 55) . "\n";

if ($evolutionAlive && $instanciaAtiva) {
    try {
        $testPayload = [
            'number' => '5511999999999',  // número de teste
            'text' => '🤖 Teste de conexão - ' . date('Y-m-d H:i:s'),
        ];
        
        $response = Http::timeout(10)
            ->withHeaders(['apikey' => $evolutionKey])
            ->post($evolutionUrl . '/message/sendText/N8n', $testPayload);
        
        if ($response->successful()) {
            $sucessos[] = "✅ Teste de envio bem-sucedido (status " . $response->status() . ")";
            echo "   Resposta: " . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            $erros[] = "❌ Teste de envio falhou: " . $response->status();
            echo "   Resposta: " . $response->body() . "\n";
        }
    } catch (\Exception $e) {
        $avisos[] = "⚠️ Erro ao testar envio: " . $e->getMessage();
    }
} else {
    $avisos[] = "⚠️ Pulando teste de envio (Evolution ou instância não disponível)";
}

// ============================================================
// RESUMO FINAL
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║                    📋 RESUMO FINAL                      ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "✅ SUCESSOS (" . count($sucessos) . "):\n";
foreach ($sucessos as $s) {
    echo "   $s\n";
}

if (!empty($avisos)) {
    echo "\n⚠️  AVISOS (" . count($avisos) . "):\n";
    foreach ($avisos as $a) {
        echo "   $a\n";
    }
}

if (!empty($erros)) {
    echo "\n❌ ERROS (" . count($erros) . "):\n";
    foreach ($erros as $e) {
        echo "   $e\n";
    }
}

// ============================================================
// SOLUÇÕES RECOMENDADAS
// ============================================================
echo "\n\n╔════════════════════════════════════════════════════════╗\n";
echo "║               🔧 SOLUÇÕES RECOMENDADAS                 ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$solucoes = [];

if (!$instanciaAtiva) {
    $solucoes[] = [
        'titulo' => 'Ativar Instância N8n',
        'passos' => [
            '1. Acesse a interface web do Evolution: http://localhost:8080',
            '2. Localize a instância "N8n"',
            '3. Se não existir, crie uma nova instância com esse nome',
            '4. Escaneie o QR Code com seu WhatsApp',
            '5. Aguarde conexão completa'
        ]
    ];
}

if (count($erros) > 0) {
    $solucoes[] = [
        'titulo' => 'Reiniciar Serviços',
        'passos' => [
            '1. Limpar cache do Laravel:',
            '   php artisan cache:clear',
            '   php artisan config:clear',
            '2. Reiniciar fila:',
            '   php artisan queue:restart',
            '3. Verificar logs:',
            '   tail -f storage/logs/laravel.log'
        ]
    ];
}

if (count($solucoes) === 0) {
    echo "✅ Nenhuma solução necessária. Sistema aparenta estar funcionando corretamente!\n\n";
} else {
    foreach ($solucoes as $i => $solucao) {
        echo ($i + 1) . ". 🔧 {$solucao['titulo']}\n";
        echo str_repeat("─", 55) . "\n";
        foreach ($solucao['passos'] as $passo) {
            echo "   $passo\n";
        }
        echo "\n";
    }
}

// ============================================================
// STATUS FINAL
// ============================================================
$statusGeral = empty($erros) ? "✅ OPERACIONAL" : "❌ COM PROBLEMAS";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  Status Final: $statusGeral                    ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

exit(empty($erros) ? 0 : 1);

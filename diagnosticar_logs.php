<?php

/**
 * DIAGNÓSTICO: Por que os logs não estão atualizando?
 */

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 DIAGNÓSTICO: Logs não estão atualizando               ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// ==== TESTE 1: Servidor Laravel está rodando? ====
echo "1️⃣  VERIFICANDO SE LARAVEL ESTÁ RODANDO...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$laravelUrl = "http://localhost:8000";

$ch = curl_init($laravelUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ LARAVEL NÃO ESTÁ RODANDO!\n\n";
    echo "Erro: $error\n\n";
    echo "💡 Solução:\n";
    echo "   1. Abra um novo PowerShell\n";
    echo "   2. Execute: php artisan serve\n";
    echo "   3. Você deve ver: 'Starting Laravel development server'\n\n";
    exit(1);
} else {
    echo "✅ Laravel está rodando em $laravelUrl\n\n";
}

// ==== TESTE 2: Arquivo de log existe e está atualizando? ====
echo "2️⃣  VERIFICANDO ARQUIVO DE LOG...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ ARQUIVO DE LOG NÃO ENCONTRADO!\n";
    echo "   Esperado: $logFile\n\n";
    echo "💡 Solução:\n";
    echo "   Execute: php artisan storage:link\n";
    echo "   Ou crie manualmente: mkdir -p storage/logs\n\n";
    exit(1);
} else {
    $fileSize = filesize($logFile);
    $fileTime = filemtime($logFile);
    $fileAgeSeconds = time() - $fileTime;
    $fileAgeMinutes = round($fileAgeSeconds / 60);
    
    echo "✅ Arquivo encontrado: $logFile\n";
    echo "   Tamanho: " . number_format($fileSize) . " bytes\n";
    echo "   Última atualização: há $fileAgeMinutes minutos\n\n";
    
    if ($fileAgeMinutes > 30) {
        echo "⚠️  O arquivo não foi atualizado há muito tempo!\n";
        echo "   Isto significa que as mensagens NÃO estão sendo processadas.\n\n";
    }
}

// ==== TESTE 3: Permissões de escrita ====
echo "3️⃣  VERIFICANDO PERMISSÕES DE ESCRITA...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$storageDir = __DIR__ . '/storage';

if (is_writable($storageDir)) {
    echo "✅ Pasta storage tem permissão de escrita\n\n";
} else {
    echo "❌ Pasta storage NÃO tem permissão de escrita!\n";
    echo "   Pasta: $storageDir\n\n";
    echo "💡 Solução (Windows):\n";
    echo "   1. Clique direito na pasta storage\n";
    echo "   2. Propriedades → Segurança\n";
    echo "   3. Editar → Seu usuário → Marcar 'Modificar'\n";
    echo "   4. Aplicar → OK\n\n";
    exit(1);
}

// ==== TESTE 4: Fila de mensagens ====
echo "4️⃣  VERIFICANDO FILA DE MENSAGENS...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    $db = $app->make('db');
    
    $pendingJobs = $db->table('jobs')->count();
    $failedJobs = $db->table('failed_jobs')->count();
    
    echo "✅ Jobs na fila: $pendingJobs\n";
    echo "⚠️  Jobs falhados: $failedJobs\n\n";
    
    if ($failedJobs > 0) {
        echo "⚠️  HAY JOBS FALHADOS!\n";
        echo "   Execute: php artisan queue:failed\n\n";
    }
} catch (Exception $e) {
    echo "⚠️  Não consegui verificar fila\n";
    echo "   Erro: " . $e->getMessage() . "\n\n";
}

// ==== TESTE 5: Webhook recebendo? ====
echo "5️⃣  VERIFICANDO WEBHOOK...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Para testar o webhook:\n\n";
echo "   php testar_webhook.php \"Teste\" 553199380844\n\n";

// ==== RESUMO ====
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 RESUMO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Se os logs não estão atualizando, verifique:\n\n";

echo "1️⃣  [ ] Laravel está rodando? (php artisan serve)\n";
echo "2️⃣  [ ] Arquivo de log tem permissão de escrita?\n";
echo "3️⃣  [ ] Webhook está configurado na Evolution?\n";
echo "4️⃣  [ ] A fila de jobs está processando?\n";
echo "5️⃣  [ ] Há algum erro no servidor (verifique terminal)?\n\n";

echo "Próximo passo:\n";
echo "1. Certifique-se de que 'php artisan serve' está rodando\n";
echo "2. Envie uma mensagem de teste pelo WhatsApp\n";
echo "3. Execute novamente: php monitorar_logs.php\n\n";

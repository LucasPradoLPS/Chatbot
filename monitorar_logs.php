<?php

/**
 * MONITOR: Acompanhar mensagens em tempo real
 * 
 * Este script monitora os logs e mostra as últimas 30 linhas
 * Ajuda a diagnosticar por que o bot não está respondendo
 */

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  📡 MONITOR: Últimas Mensagens e Erros                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Arquivo de log não encontrado!\n";
    echo "   Esperado em: $logFile\n\n";
    exit(1);
}

// Ler últimas linhas do arquivo
$lines = file($logFile);
$totalLines = count($lines);

// Mostrar últimas 30 linhas (mais recentes primeiro)
echo "📋 ÚLTIMAS 30 LINHAS DOS LOGS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$recentLines = array_slice($lines, max(0, $totalLines - 30));

foreach ($recentLines as $line) {
    echo trim($line) . "\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Análise dos logs
echo "🔍 ANÁLISE:\n\n";

$content = implode("\n", $lines);

// Procurar por eventos importantes
$hasWebhookReceived = strpos($content, 'Webhook received') !== false;
$hasProcessStart = strpos($content, 'ProcessWhatsappMessage: start') !== false;
$hasMenuResponse = strpos($content, '[MENU] Resposta enviada') !== false;
$hasErrors = strpos($content, 'ERROR') !== false;
$hasBlockedEvents = strpos($content, '[BLOQUEADO]') !== false;

echo "   ✅ Webhook recebido?: " . ($hasWebhookReceived ? "SIM" : "NÃO") . "\n";
echo "   ✅ Processamento iniciado?: " . ($hasProcessStart ? "SIM" : "NÃO") . "\n";
echo "   ✅ Resposta enviada?: " . ($hasMenuResponse ? "SIM" : "NÃO") . "\n";
echo "   ⚠️  Há erros?: " . ($hasErrors ? "SIM - VERIFIQUE!" : "NÃO") . "\n";
echo "   ⚠️  Eventos bloqueados?: " . ($hasBlockedEvents ? "SIM - VERIFIQUE!" : "NÃO") . "\n\n";

// Procurar por erros específicos
if ($hasErrors) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "❌ ERROS ENCONTRADOS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    foreach ($lines as $line) {
        if (strpos($line, 'ERROR') !== false) {
            echo trim($line) . "\n\n";
        }
    }
}

// Dicas de diagnóstico
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 O QUE FAZER:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$hasWebhookReceived) {
    echo "1️⃣  Webhook NÃO está sendo recebido\n";
    echo "    → Verifique se a URL está correta na Evolution\n";
    echo "    → URL deve ser: http://localhost:8000/api/webhook/whatsapp\n";
    echo "    → Ou seu IP: http://192.168.x.x:8000/api/webhook/whatsapp\n\n";
} else if (!$hasProcessStart) {
    echo "1️⃣  Webhook recebido mas não processado\n";
    echo "    → Pode ser um problema na deduplicação\n";
    echo "    → Ou a mensagem está sendo bloqueada\n\n";
} else if (!$hasMenuResponse) {
    echo "1️⃣  Processamento iniciado mas resposta não enviada\n";
    echo "    → Pode ser erro na IA ou na Evolution API\n";
    echo "    → Verifique acima os erros reportados\n\n";
} else {
    echo "1️⃣  Tudo parece estar funcionando nos logs!\n";
    echo "    → O bot processou e enviou a resposta\n";
    echo "    → Se não recebeu, pode ser problema no WhatsApp\n\n";
}

echo "2️⃣  Envie uma mensagem de teste pelo WhatsApp\n";
echo "    → Execute novamente este script\n";
echo "    → Os logs devem atualizar\n\n";

echo "3️⃣  Se ver 'ERROR', copie a mensagem completa\n";
echo "    → Pode indicar qual é o problema\n\n";

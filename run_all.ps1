#!/usr/bin/env pwsh
# Script para executar tudo em um terminal

Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  INICIANDO BOT E TESTANDO" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Parar processos PHP existentes
Write-Host "1️⃣  Parando processos PHP anteriores..." -ForegroundColor Yellow
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

# Limpar cache e config
Write-Host "2️⃣  Limpando cache e config..." -ForegroundColor Yellow
cd "c:\Users\lucas\Downloads\Chatbot-laravel"
php artisan cache:clear --quiet 2>&1 | Out-Null
php artisan config:clear --quiet 2>&1 | Out-Null

# Limpar logs
Write-Host "3️⃣  Limpando logs..." -ForegroundColor Yellow
"" | Out-File -FilePath "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log" -Encoding UTF8 -Force

Write-Host ""
Write-Host "4️⃣  Iniciando servidor Laravel..." -ForegroundColor Cyan
Write-Host "   (Ctrl+C para parar o servidor)" -ForegroundColor Gray
Write-Host ""

# Iniciar servidor em background
$serverProcess = Start-Process -FilePath php -ArgumentList @("artisan", "serve", "--host=127.0.0.1", "--port=8000") -NoNewWindow -PassThru

# Aguardar servidor iniciar
Start-Sleep -Seconds 5

# Verificar se servidor está rodando
$portCheck = netstat -ano 2>&1 | Select-String ":8000"
if ($null -ne $portCheck) {
    Write-Host "✓ Servidor rodando na porta 8000" -ForegroundColor Green
}
else {
    Write-Host "❌ Servidor NÃO conseguiu iniciar na porta 8000" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  TESTANDO BOT" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Enviar mensagem de teste
Write-Host "5️⃣  Enviando mensagem de teste..." -ForegroundColor Yellow
php test_bot_correto.php "Oi" 2>&1

Write-Host ""
Write-Host "6️⃣  Verificando logs..." -ForegroundColor Yellow
Write-Host ""
Get-Content "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log" -Tail 20

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  AGUARDANDO RESPOSTAS..." -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "Aguardando 30 segundos para OpenAI processar..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

Write-Host ""
Write-Host "Logs finais:" -ForegroundColor Cyan
Get-Content "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log" -Tail 30

Write-Host ""
Write-Host "Encerrando servidor..." -ForegroundColor Yellow
Stop-Process -Id $serverProcess.Id -Force -ErrorAction SilentlyContinue
Write-Host "✓ Concluído!" -ForegroundColor Green

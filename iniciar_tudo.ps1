#!/usr/bin/env pwsh
# Script para iniciar Laravel Server e Queue Worker em paralelo

$projectPath = "c:\Users\lucas\Downloads\Chatbot-laravel"
cd $projectPath

Write-Host "🚀 Iniciando Chatbot Laravel..." -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green

# Limpar logs antigos
Write-Host "Limpando logs antigos..." -ForegroundColor Yellow
if (Test-Path "storage/logs/laravel.log") {
	Clear-Content "storage/logs/laravel.log" -ErrorAction SilentlyContinue
}

# Iniciar em múltiplos processos
Write-Host ""
Write-Host "Iniciando servidor Laravel..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$projectPath'; php -d max_execution_time=0 artisan serve --host=0.0.0.0 --port=8000" -WindowStyle Normal

Start-Sleep -Seconds 3

Write-Host "Aguardando servidor iniciar..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

Write-Host ""
Write-Host "Iniciando worker de filas (auto-restart)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$projectPath'; cmd /c iniciar_worker.bat" -WindowStyle Normal

Write-Host ""
Write-Host "Tudo iniciado." -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
Write-Host ""
Write-Host "Servidor: http://localhost:8000" -ForegroundColor Cyan
Write-Host "Webhook: http://localhost:8000/api/webhook/whatsapp" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para enviar mensagem de teste:" -ForegroundColor Yellow
Write-Host "   php enviar_mensagem.php 'Sua mensagem aqui'" -ForegroundColor White
Write-Host ""
Write-Host "Para monitorar logs:" -ForegroundColor Yellow
Write-Host "   Get-Content storage/logs/laravel.log -Tail 100 -Wait" -ForegroundColor White

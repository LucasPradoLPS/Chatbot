#!/usr/bin/env pwsh
<#
 Script unificado para rodar servidor Laravel e worker em um único terminal
 Uso: .\rodar_tudo.ps1
#>

$ErrorActionPreference = "SilentlyContinue"

# Ir para a pasta do projeto
Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "`n╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║       CHATBOT LARAVEL - CONTROLADOR UNIFICADO                 ║" -ForegroundColor Cyan
Write-Host "║       Servidor + Worker em um único terminal                  ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Parar processos antigos
Write-Host "🧹 Limpando processos antigos..." -ForegroundColor Yellow
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

# Limpar logs
Remove-Item storage/logs/laravel.log -ErrorAction SilentlyContinue

Write-Host "✅ Limpo!`n" -ForegroundColor Green

# Iniciar servidor em background
Write-Host "🚀 Iniciando Laravel Server (porta 8000)..." -ForegroundColor Green
$serverJob = Start-Job -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    & php artisan serve --port=8000 2>&1
}
Start-Sleep -Seconds 5
Write-Host "✅ Servidor iniciado`n" -ForegroundColor Green

# Iniciar worker em background
Write-Host "🔄 Iniciando Queue Worker..." -ForegroundColor Green
$workerJob = Start-Job -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    & php artisan queue:work 2>&1
}
Start-Sleep -Seconds 3
Write-Host "✅ Worker iniciado`n" -ForegroundColor Green

# Exibir status
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  ✅ SISTEMA PRONTO PARA RECEBER MENSAGENS                      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Write-Host "📍 Servidor: http://localhost:8000" -ForegroundColor Yellow
Write-Host "📍 Webhook: http://localhost:8000/api/webhook/whatsapp" -ForegroundColor Yellow
Write-Host "📍 Evolution API: http://localhost:8080`n" -ForegroundColor Yellow

Write-Host "💬 Para enviar mensagem:" -ForegroundColor Cyan
Write-Host "   php enviar_mensagem.php 'Ola!'" -ForegroundColor White
Write-Host "`n📊 Para ver logs:" -ForegroundColor Cyan
Write-Host "   Get-Content storage/logs/laravel.log -Tail 50 -Wait`n" -ForegroundColor White

Write-Host "🔴 Para parar (Ctrl+C)..." -ForegroundColor Red

# Monitorar e exibir logs em tempo real
Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "📋 LOGS DO SISTEMA" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`n" -ForegroundColor Cyan

try {
    # Exibir logs em tempo real
    Get-Content storage/logs/laravel.log -Tail 20 -Wait
}
catch {
    Write-Host "Aguardando logs..." -ForegroundColor Gray
    Start-Sleep -Seconds 5
    Get-Content storage/logs/laravel.log -Tail 50 -Wait
}

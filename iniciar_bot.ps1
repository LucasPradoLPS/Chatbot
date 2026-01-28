# Script PowerShell para rodar servidor e worker
$ErrorActionPreference = "SilentlyContinue"
Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "`n===================================" -ForegroundColor Green
Write-Host "  BOT INICIANDO..." -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green

# Parar processos antigos
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

# Iniciar servidor em background
Write-Host "`n[1/2] Iniciando Servidor Laravel..." -ForegroundColor Cyan
$server = Start-Job -Name "server" -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    php artisan serve --port=8000 2>&1
}
Start-Sleep -Seconds 5

# Iniciar worker em background  
Write-Host "[2/2] Iniciando Queue Worker..." -ForegroundColor Cyan
$worker = Start-Job -Name "worker" -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    php artisan queue:work 2>&1
}

Write-Host "`n✅ Sistema iniciado!" -ForegroundColor Green
Write-Host "`n📱 Servidor: http://localhost:8000" -ForegroundColor Yellow
Write-Host "📱 WhatsApp: Envie mensagens normalmente" -ForegroundColor Yellow
Write-Host "`n" -ForegroundColor Yellow

# Exibir logs em tempo real
Write-Host "===================================" -ForegroundColor Cyan
Write-Host "  LOGS DO SISTEMA" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""

# Exibir outputs
while ($true) {
    Receive-Job -Job $server -ErrorAction SilentlyContinue | Write-Host
    Receive-Job -Job $worker -ErrorAction SilentlyContinue | Write-Host
    Start-Sleep -Seconds 1
}

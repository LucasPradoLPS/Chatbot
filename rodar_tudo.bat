@echo off
REM Script unificado para rodar servidor Laravel e worker em um único terminal

setlocal enabledelayedexpansion
cd /d "%~dp0"

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║       CHATBOT LARAVEL - TERMINAL UNIFICADO                     ║
echo ║       Servidor + Worker no mesmo terminal                      ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM Limpar logs
del storage\logs\laravel.log 2>nul

REM Iniciar em um novo terminal PowerShell
echo Abrindo terminal PowerShell...
echo.

powershell -NoExit -Command {
    Set-Location 'c:\Users\lucas\Downloads\Chatbot-laravel'
    
    Write-Host "`n╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║  📱 CHATBOT LARAVEL INICIANDO...                               ║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan
    
    # Criar arquivo de controle
    $startTime = Get-Date
    Write-Host "⏱️  Hora de início: $startTime`n" -ForegroundColor Yellow
    
    # Iniciar servidor em background
    Write-Host "🚀 Iniciando Laravel Server (porta 8000)..." -ForegroundColor Green
    $serverProcess = Start-Process -NoNewWindow -PassThru php -ArgumentList "artisan serve --port=8000"
    Start-Sleep -Seconds 5
    
    Write-Host "✅ Servidor iniciado (PID: $($serverProcess.Id))`n" -ForegroundColor Green
    
    # Iniciar worker em background
    Write-Host "🔄 Iniciando Queue Worker..." -ForegroundColor Green
    $workerProcess = Start-Process -NoNewWindow -PassThru php -ArgumentList "artisan queue:work"
    Start-Sleep -Seconds 2
    
    Write-Host "✅ Worker iniciado (PID: $($workerProcess.Id))`n" -ForegroundColor Green
    
    # Exibir informações
    Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║  ✅ SISTEMA PRONTO PARA RECEBER MENSAGENS                      ║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan
    
    Write-Host "📍 Servidor: http://localhost:8000" -ForegroundColor Yellow
    Write-Host "📍 Webhook: http://localhost:8000/api/webhook/whatsapp" -ForegroundColor Yellow
    Write-Host "📍 Evolution API: http://localhost:8080`n" -ForegroundColor Yellow
    
    Write-Host "💬 Para enviar mensagem de teste:" -ForegroundColor Cyan
    Write-Host "   php enviar_mensagem.php 'Sua mensagem aqui'" -ForegroundColor White
    Write-Host "`n📊 Para monitorar logs:" -ForegroundColor Cyan
    Write-Host "   Get-Content storage/logs/laravel.log -Tail 50 -Wait`n" -ForegroundColor White
    
    Write-Host "⏹️  Para parar: Feche esta janela`n" -ForegroundColor Red
    
    # Manter os processos rodando
    while ($true) {
        if (-not (Get-Process -Id $serverProcess.Id -ErrorAction SilentlyContinue)) {
            Write-Host "⚠️  Servidor parou. Reiniciando..." -ForegroundColor Yellow
            $serverProcess = Start-Process -NoNewWindow -PassThru php -ArgumentList "artisan serve --port=8000"
            Start-Sleep -Seconds 3
        }
        
        if (-not (Get-Process -Id $workerProcess.Id -ErrorAction SilentlyContinue)) {
            Write-Host "⚠️  Worker parou. Reiniciando..." -ForegroundColor Yellow
            $workerProcess = Start-Process -NoNewWindow -PassThru php -ArgumentList "artisan queue:work"
            Start-Sleep -Seconds 3
        }
        
        Start-Sleep -Seconds 5
    }
}

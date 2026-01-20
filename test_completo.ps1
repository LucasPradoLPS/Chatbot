#!/usr/bin/env pwsh

# Configurar para PowerShell scripts
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force

$projectPath = "c:\Users\lucas\Downloads\Chatbot-laravel"
cd $projectPath

Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  BOT ASSISTANT - INICIALIZANDO TESTE" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# 1. Parar processos anteriores
Write-Host "📌 Etapa 1: Limpeza" -ForegroundColor Yellow
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue | Out-Null
Start-Sleep -Seconds 1

# Limpar cache e logs
php artisan cache:clear --quiet 2>$null
php artisan config:clear --quiet 2>$null
"" | Out-File -FilePath "storage\logs\laravel.log" -Encoding UTF8 -Force

Write-Host "✓ Limpeza concluída" -ForegroundColor Green
Write-Host ""

# 2. Iniciar servidor em background silenciosamente
Write-Host "📌 Etapa 2: Iniciando servidor" -ForegroundColor Yellow

$serverJob = Start-Job -ScriptBlock {
    cd "c:\Users\lucas\Downloads\Chatbot-laravel"
    & php artisan serve --host=127.0.0.1 --port=8000 2>&1
}

Start-Sleep -Seconds 5

# Verificar se servidor está rodando
$portTest = netstat -ano 2>&1 | Select-String ":8000" | Measure-Object | Select-Object -ExpandProperty Count
if ($portTest -gt 0) {
    Write-Host "✓ Servidor iniciado com sucesso (127.0.0.1:8000)" -ForegroundColor Green
} else {
    Write-Host "❌ Servidor falhou ao iniciar" -ForegroundColor Red
    Stop-Job -Job $serverJob -ErrorAction SilentlyContinue
    exit 1
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  TESTANDO BOT" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# 3. Enviar mensagem
Write-Host "📌 Etapa 3: Enviando mensagem de teste" -ForegroundColor Yellow
php test_bot_correto.php "Oi"

# 4. Aguardar processamento
Write-Host ""
Write-Host "📌 Etapa 4: Aguardando resposta (máx 30 segundos)" -ForegroundColor Yellow

$timeout = 30
$startTime = Get-Date
$found = $false

while ((New-TimeSpan -Start $startTime -End (Get-Date)).TotalSeconds -lt $timeout) {
    $logContent = Get-Content "storage\logs\laravel.log" -ErrorAction SilentlyContinue -Tail 1
    
    if ($logContent -match "completed|erro|Timeout" -or $logContent -match "respondeu") {
        $found = $true
        break
    }
    
    Write-Host "." -NoNewline
    Start-Sleep -Seconds 1
}

Write-Host ""
Write-Host ""

# 5. Mostrar logs
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  RESUMO DOS LOGS" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

$logs = Get-Content "storage\logs\laravel.log" -Tail 60 -ErrorAction SilentlyContinue

if ($logs) {
    # Mostrar logs com cores
    foreach ($line in $logs) {
        if ($line -match "ERROR|❌") {
            Write-Host $line -ForegroundColor Red
        } elseif ($line -match "completed|✓") {
            Write-Host $line -ForegroundColor Green
        } elseif ($line -match "Status da IA") {
            Write-Host $line -ForegroundColor Gray
        } else {
            Write-Host $line
        }
    }
} else {
    Write-Host "Nenhum log disponível" -ForegroundColor Red
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor Cyan

# 6. Parar servidor
Write-Host "📌 Etapa 5: Encerrando servidor" -ForegroundColor Yellow
Stop-Job -Job $serverJob -ErrorAction SilentlyContinue
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue | Out-Null

Write-Host "✓ Teste concluído!" -ForegroundColor Green
Write-Host ""

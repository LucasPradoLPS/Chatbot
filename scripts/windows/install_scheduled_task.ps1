param(
    [string]$TaskName = 'ChatbotLaravel',
    [string]$ProjectPath = "C:\Users\lucas\Downloads\Chatbot-laravel"
)

$ErrorActionPreference = 'Stop'

$scriptPath = Join-Path $ProjectPath 'scripts\windows\run_chatbot.ps1'
if (-not (Test-Path $scriptPath)) {
    throw "Não encontrei $scriptPath"
}

# Remove tarefa antiga (se existir)
if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
}

$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`" -ProjectPath `"$ProjectPath`""
$trigger = New-ScheduledTaskTrigger -AtLogOn

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1)


# Nesta versão do módulo ScheduledTasks, o enum é 'Interactive'.
# Tenta registrar com RunLevel Highest; se não tiver permissão, cai para Limited.
$principalHighest = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest
$registered = $false
try {
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principalHighest | Out-Null
    $registered = $true
} catch {
    Write-Host "Aviso: não foi possível registrar com RunLevel Highest. Tentando Limited..." -ForegroundColor Yellow
    $principalLimited = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principalLimited | Out-Null
    $registered = $true
}

if (-not $registered) {
    throw "Falha ao registrar a tarefa '$TaskName'."
}

Write-Host "OK: Tarefa '$TaskName' instalada para iniciar no login." -ForegroundColor Green
Write-Host "Script: $scriptPath"
Write-Host "Logs:  $ProjectPath\storage\logs\chatbot-runner.log"

try {
    Start-ScheduledTask -TaskName $TaskName
    Write-Host "OK: Tarefa '$TaskName' iniciada agora." -ForegroundColor Green
} catch {
    Write-Host "Aviso: não consegui iniciar a tarefa automaticamente. Você pode iniciar manualmente pelo Agendador de Tarefas." -ForegroundColor Yellow
}

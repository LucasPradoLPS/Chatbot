param(
    [string]$ProjectPath = "C:\Users\lucas\Downloads\Chatbot-laravel"
)

$ErrorActionPreference = 'Stop'

$startupDir = [Environment]::GetFolderPath('Startup')
if (-not $startupDir) {
    throw 'Não consegui localizar a pasta Startup do Windows.'
}

$sourceCmd = Join-Path $ProjectPath 'scripts\windows\startup_chatbot.cmd'
if (-not (Test-Path $sourceCmd)) {
    throw "Não encontrei $sourceCmd"
}

$destCmd = Join-Path $startupDir 'ChatbotLaravel.cmd'
Copy-Item -Force $sourceCmd $destCmd

Write-Host "OK: Instalado Startup item em: $destCmd" -ForegroundColor Green
Write-Host "No próximo login, o bot inicia automaticamente." -ForegroundColor Green

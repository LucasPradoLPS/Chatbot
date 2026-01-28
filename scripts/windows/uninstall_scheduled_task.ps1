param(
    [string]$TaskName = 'ChatbotLaravel'
)

$ErrorActionPreference = 'Stop'

if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    Write-Host "OK: Tarefa '$TaskName' removida." -ForegroundColor Yellow
} else {
    Write-Host "Nada a remover: tarefa '$TaskName' não existe." -ForegroundColor Gray
}

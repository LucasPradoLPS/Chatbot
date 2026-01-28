param()
Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "`n" -ForegroundColor Cyan
Write-Host "CHATBOT LARAVEL - CONTROLADOR UNIFICADO" -ForegroundColor Cyan
Write-Host "Servidor + Worker em um terminal" -ForegroundColor Cyan
Write-Host "`n" -ForegroundColor Cyan

# Parar processos antigos
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

Write-Host "Iniciando Laravel Server..." -ForegroundColor Green
$serverJob = Start-Job -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    & php -d max_execution_time=0 artisan serve --port=8000
}
Start-Sleep -Seconds 5

Write-Host "Iniciando Queue Worker..." -ForegroundColor Green
$workerJob = Start-Job -ScriptBlock {
    Set-Location "c:\Users\lucas\Downloads\Chatbot-laravel"
    & php -d max_execution_time=0 artisan queue:work --queue=default,handoff --sleep=1 --tries=1 --timeout=120
}
Start-Sleep -Seconds 3

Write-Host "`n" -ForegroundColor Cyan
Write-Host "Sistema pronto!" -ForegroundColor Green
Write-Host "Servidor: http://localhost:8000" -ForegroundColor Yellow
Write-Host "`n" -ForegroundColor Cyan

Write-Host "Para enviar mensagem:" -ForegroundColor Cyan
Write-Host "php enviar_mensagem.php 'Sua mensagem'" -ForegroundColor White
Write-Host "`n" -ForegroundColor Cyan

Write-Host "Aguardando comandos..." -ForegroundColor Yellow

# Manter rodando
while ($true) {
    Start-Sleep -Seconds 60
}

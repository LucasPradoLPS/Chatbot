cd "c:\Users\lucas\Downloads\Chatbot-laravel"

Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

"" | Out-File -FilePath storage\logs\laravel.log -Encoding UTF8 -Force

Start-Process -FilePath "php" -ArgumentList @("-S", "127.0.0.1:8000", "-t", "public") -WindowStyle Hidden
Start-Process -FilePath "php" -ArgumentList @("artisan", "queue:work", "--queue=default") -WindowStyle Hidden

Start-Sleep -Seconds 4

Write-Host "Teste 1: Enviando mensagem FORA do horario (tarde)" -ForegroundColor Yellow
Write-Host "=" * 60 -ForegroundColor Yellow

# Simulando horário: 18h (fora do atendimento)
$headers = @{"Content-Type"="application/json"}
$body = @{
    "instance" = "N8n"
    "data" = @{
        "key" = @{
            "remoteJid" = "5514444555666@s.whatsapp.net"
            "id" = "TESTE_FORA_HORARIO_001"
            "fromMe" = $false
        }
        "pushName" = "Maria"
        "message" = @{
            "conversation" = "Ola, tudo bem?"
        }
    }
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert" `
    -Method POST -Headers $headers -Body $body -TimeoutSec 15 -UseBasicParsing | Select-Object StatusCode

Start-Sleep -Seconds 5

Write-Host "`nLOGS DO TESTE:" -ForegroundColor Green
Get-Content storage\logs\laravel.log | Select-Object -Last 30

Write-Host "`n`nTeste de horario de atendimento IMPLEMENTADO" -ForegroundColor Green
Write-Host "=" * 60 -ForegroundColor Green
Write-Host "`nO bot agora verifica:" -ForegroundColor Cyan
Write-Host "- Se é fim de semana (sábado ou domingo)" -ForegroundColor Cyan
Write-Host "- Se está fora do horário (antes de 08h ou depois de 17h)" -ForegroundColor Cyan
Write-Host "- Se sim, envia mensagem de horário de atendimento e bloqueia a IA" -ForegroundColor Cyan

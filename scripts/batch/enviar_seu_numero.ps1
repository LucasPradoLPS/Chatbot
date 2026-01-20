cd "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "Preparando para enviar mensagem no seu numero..." -ForegroundColor Cyan

Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

"" | Out-File -FilePath storage\logs\laravel.log -Encoding UTF8 -Force

Start-Process -FilePath "php" -ArgumentList @("-S", "127.0.0.1:8000", "-t", "public") -WindowStyle Hidden
Start-Process -FilePath "php" -ArgumentList @("artisan", "queue:work", "--queue=default") -WindowStyle Hidden

Start-Sleep -Seconds 4

Write-Host "`nEnviando mensagem..." -ForegroundColor Green

# Número com DDD: 55 (Brasil) + 11 (São Paulo) + 999380844
# Resultado: 5511999380844

$headers = @{"Content-Type"="application/json"}
$body = @{
    "instance" = "N8n"
    "data" = @{
        "key" = @{
            "remoteJid" = "5511999380844@s.whatsapp.net"
            "id" = "TESTE_NUMERO_USUARIO_001"
            "fromMe" = $false
        }
        "pushName" = "Lucas"
        "message" = @{
            "conversation" = "Ola"
        }
    }
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert" `
    -Method POST -Headers $headers -Body $body -TimeoutSec 15 -UseBasicParsing | Select-Object StatusCode

Write-Host "`nAguardando processamento..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "`nRESPOSTA RECEBIDA:" -ForegroundColor Green
Write-Host "==================" -ForegroundColor Green
Get-Content storage\logs\laravel.log | Select-Object -Last 80

Write-Host "`n`nNOTA IMPORTANTE:" -ForegroundColor Yellow
Write-Host "O numero usado foi: 5511999380844" -ForegroundColor Yellow
Write-Host "Formato: 55 (Brasil) + 11 (DDD) + 999380844 (seu numero)" -ForegroundColor Yellow
Write-Host "`nSe precisa de outro DDD, diga e reenvio!" -ForegroundColor Yellow

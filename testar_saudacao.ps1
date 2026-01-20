cd "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "Parando processos..." -ForegroundColor Yellow
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

Write-Host "Limpando logs..." -ForegroundColor Yellow
"" | Out-File -FilePath storage\logs\laravel.log -Encoding UTF8 -Force

Write-Host "Iniciando servidores..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList @("-S", "127.0.0.1:8000", "-t", "public") -WindowStyle Hidden
Start-Process -FilePath "php" -ArgumentList @("artisan", "queue:work", "--queue=default") -WindowStyle Hidden

Start-Sleep -Seconds 4

Write-Host "Enviando cliente com nome..." -ForegroundColor Cyan

$headers = @{"Content-Type"="application/json"}
$body = @{
    "instance" = "N8n"
    "data" = @{
        "key" = @{
            "remoteJid" = "5513333444555@s.whatsapp.net"
            "id" = "TESTE_NOME_JOAO"
            "fromMe" = $false
        }
        "pushName" = "Joao Silva"
        "message" = @{
            "conversation" = "Ola"
        }
    }
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert" `
    -Method POST -Headers $headers -Body $body -TimeoutSec 15 -UseBasicParsing | Select-Object StatusCode

Start-Sleep -Seconds 10

Write-Host "`nRESPOSTA RECEBIDA:" -ForegroundColor Green
Get-Content storage\logs\laravel.log | Select-Object -Last 80

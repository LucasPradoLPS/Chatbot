cd "c:\Users\lucas\Downloads\Chatbot-laravel"

Write-Host "Parando processos PHP..." -ForegroundColor Yellow
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

Write-Host "Limpando logs..." -ForegroundColor Yellow
"" | Out-File -FilePath storage\logs\laravel.log -Encoding UTF8 -Force

Write-Host "Iniciando servidor web..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList @("-S", "127.0.0.1:8000", "-t", "public") -WindowStyle Hidden

Write-Host "Iniciando queue worker..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList @("artisan", "queue:work", "--queue=default", "--max-time=600") -WindowStyle Hidden

Start-Sleep -Seconds 4

Write-Host "ENVIANDO MENSAGEM DE TESTE" -ForegroundColor Cyan

$headers = @{"Content-Type"="application/json"}
$body = @{
    "instance" = "N8n"
    "data" = @{
        "key" = @{
            "remoteJid" = "5511987654321@s.whatsapp.net"
            "id" = "MSG_CALIFORNIA_001"
            "fromMe" = $false
        }
        "message" = @{
            "conversation" = "Ola, gostaria de saber sobre imoveis a venda"
        }
    }
} | ConvertTo-Json

Write-Host "Enviando mensagem..." -ForegroundColor Cyan

$response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert" `
    -Method POST `
    -Headers $headers `
    -Body $body `
    -TimeoutSec 15 `
    -UseBasicParsing

Write-Host "Resposta: HTTP $($response.StatusCode)" -ForegroundColor Green

Write-Host "Aguardando processamento..." -ForegroundColor Yellow
Start-Sleep -Seconds 8

Write-Host "LOGS DO PROCESSAMENTO:" -ForegroundColor Green
Write-Host "=====================" -ForegroundColor Green

Get-Content storage\logs\laravel.log -ErrorAction SilentlyContinue | Select-Object -Last 80

Write-Host "`nTESTE CONCLUIDO" -ForegroundColor Green

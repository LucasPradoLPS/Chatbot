# Kill any existing PHP processes
Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

cd "c:\Users\lucas\Downloads\Chatbot-laravel"

# Clear logs
"" | Out-File -FilePath storage\logs\laravel.log -Encoding UTF8 -Force

# Start web server in new window (not hidden, so we can see it)
Write-Host "Starting Web Server on port 8000..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList @("-S", "127.0.0.1:8000", "-t", "public") -WindowStyle Normal

Start-Sleep -Seconds 3

# Start queue worker in new window
Write-Host "Starting Queue Worker..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList @("artisan", "queue:work", "--queue=default", "--max-time=600") -WindowStyle Normal

Start-Sleep -Seconds 3

# Test ping first
Write-Host "Testing ping endpoint..." -ForegroundColor Cyan
try {
    $pingTest = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/ping" -Method GET -UseBasicParsing -TimeoutSec 5
    Write-Host "Ping response: $($pingTest.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "Ping failed: $_" -ForegroundColor Red
}

Start-Sleep -Seconds 2

# Send test message to webhook
Write-Host "Sending test message to webhook..." -ForegroundColor Cyan
$headers = @{"Content-Type"="application/json"}
$body = @{
    "instance" = "N8n"
    "data" = @{
        "key" = @{
            "remoteJid" = "5511999888777@s.whatsapp.net"
            "id" = "TEST_FINAL_001"
            "fromMe" = $false
        }
        "message" = @{
            "conversation" = "Oi"
        }
    }
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/webhook/whatsapp/messages-upsert" `
        -Method POST `
        -Headers $headers `
        -Body $body `
        -TimeoutSec 15 `
        -UseBasicParsing
    Write-Host "Webhook response: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "Webhook error: $_" -ForegroundColor Yellow
}

# Wait for processing
Write-Host "Waiting for processing..." -ForegroundColor Cyan
Start-Sleep -Seconds 8

# Read logs
Write-Host "`n=== LOGS ===" -ForegroundColor Cyan
Get-Content storage\logs\laravel.log -ErrorAction SilentlyContinue | Tail -100

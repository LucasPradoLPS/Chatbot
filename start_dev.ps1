# Starts Laravel HTTP server + queue worker in the background (Windows)
# Usage: powershell -ExecutionPolicy Bypass -File .\start_dev.ps1

$ErrorActionPreference = 'SilentlyContinue'

Push-Location "$PSScriptRoot"

# Stop existing php processes (best-effort)
Get-Process php | Stop-Process -Force
Start-Sleep -Seconds 1

# Start HTTP server (built-in PHP) on port 8000
$server = Start-Process -FilePath "php" -ArgumentList @(
  "-d","max_execution_time=0",
  "-S","127.0.0.1:8000",
  "-t","public"
) -WindowStyle Hidden -PassThru

# Start queue worker
$worker = Start-Process -FilePath "php" -ArgumentList @(
  "-d","max_execution_time=0",
  "artisan","queue:work",
  "--queue=default",
  "--sleep=1",
  "--tries=1",
  "--timeout=120"
) -WindowStyle Hidden -PassThru

Write-Host "Server PID=$($server.Id) Worker PID=$($worker.Id)" -ForegroundColor Green

# Quick health check
Start-Sleep -Seconds 2
try {
  $status = (Invoke-WebRequest -UseBasicParsing http://127.0.0.1:8000/ -TimeoutSec 5).StatusCode
  Write-Host "HTTP health: $status" -ForegroundColor Green
} catch {
  Write-Host "HTTP health: FAILED - $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Tail logs: Get-Content .\storage\logs\laravel.log -Tail 80 -Wait" -ForegroundColor Cyan

Pop-Location

@echo off
REM Script para testar o bot via PowerShell

setlocal enabledelayedexpansion

echo ================================
echo TESTANDO BOT - ENDPOINTS
echo ================================
echo.

REM Aguardar servidor ficar pronto
echo Aguardando servidor...
timeout /t 3 /nobreak

REM Teste 1: Ping básico
echo.
echo [1] Testando GET /api/ping
powershell -Command ^
  "$response = Invoke-WebRequest -Uri 'http://localhost:8000/api/ping' -ErrorAction SilentlyContinue; ^
   if ($?) { ^
     Write-Host '✅ Sucesso!' -ForegroundColor Green; ^
     Write-Host ($response.Content | ConvertFrom-Json | ConvertTo-Json) ^
   } else { ^
     Write-Host '❌ Erro ao conectar' -ForegroundColor Red ^
   }"

echo.
echo [2] Testando GET /api/debug/logs
powershell -Command ^
  "$response = Invoke-WebRequest -Uri 'http://localhost:8000/api/debug/logs' -ErrorAction SilentlyContinue; ^
   if ($?) { ^
     Write-Host '✅ Sucesso!' -ForegroundColor Green; ^
     $data = $response.Content | ConvertFrom-Json; ^
     Write-Host ('Total de arquivos: ' + @($data.files).Length) ^
   } else { ^
     Write-Host '❌ Erro ao conectar' -ForegroundColor Red ^
   }"

echo.
echo ================================
echo TESTES CONCLUIDOS
echo ================================

@echo off
REM Script para manter o Laravel server rodando continuamente
REM Reinicia automaticamente se cair

setlocal enabledelayedexpansion
cd /d "%~dp0"

set PORT=8000
set HOST=127.0.0.1
set RESTARTS=0
set MAX_RETRIES=10

echo.
echo ====================================
echo  INICIADOR ROBUSTO - LARAVEL SERVER
echo ====================================
echo.

:start_server
cls
echo [%date% %time%] Iniciando servidor Laravel (tentativa %RESTARTS%)...
echo.

php -d max_execution_time=600 artisan serve --host=%HOST% --port=%PORT%

REM Se chegou aqui, o servidor caiu
set /a RESTARTS=%RESTARTS% + 1

if %RESTARTS% gtr %MAX_RETRIES% (
    echo.
    echo ❌ ERRO: Servidor falhou %MAX_RETRIES% vezes. Abortando.
    pause
    exit /b 1
)

echo.
echo ⚠️  Servidor caiu. Reiniciando em 3 segundos (tentativa %RESTARTS%/%MAX_RETRIES%)...
echo.
timeout /t 3 /nobreak

goto start_server

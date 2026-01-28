@echo off
REM Script para manter o queue worker rodando continuamente (Windows)

setlocal enabledelayedexpansion
cd /d "%~dp0"

set RESTARTS=0
set LOGFILE=storage\logs\queue-worker.log

if not exist "storage\logs" (
    mkdir "storage\logs" >nul 2>&1
)

echo.
echo ====================================
echo  INICIADOR ROBUSTO - QUEUE WORKER
echo ====================================
echo.

:start_worker
echo [%date% %time%] Iniciando queue worker (reinicios=%RESTARTS%)...
echo.

echo [%date% %time%] START (reinicios=%RESTARTS%)>> "%LOGFILE%"

php -d max_execution_time=0 artisan queue:work --queue=default,handoff --tries=3 --timeout=300 --sleep=1 --memory=256 --max-jobs=500 --max-time=3600 >> "%LOGFILE%" 2>&1

REM Se chegou aqui, o worker saiu (queue:restart, crash, etc.). Vamos reiniciar.
set /a RESTARTS=%RESTARTS% + 1

echo.
echo [%date% %time%] Worker saiu. Reiniciando em 5 segundos (reinicios=%RESTARTS%)...
echo [%date% %time%] STOP (reinicios=%RESTARTS%)>> "%LOGFILE%"
echo.
timeout /t 5 /nobreak

goto start_worker

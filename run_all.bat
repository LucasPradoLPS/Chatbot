@echo off
REM Script para executar tudo em um terminal

setlocal enabledelayedexpansion

echo ═══════════════════════════════════════════════════════════
echo   INICIANDO BOT E TESTANDO
echo ═══════════════════════════════════════════════════════════
echo.

echo 1^) Parando processos PHP anteriores...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 1 >nul

echo 2^) Limpando cache e config...
cd /d "c:\Users\lucas\Downloads\Chatbot-laravel"
php artisan cache:clear --quiet 2>nul
php artisan config:clear --quiet 2>nul

echo 3^) Limpando logs...
type nul > "c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log"

echo.
echo 4^) Iniciando servidor Laravel...
echo    (Ctrl+C para parar o servidor)
echo.

REM Iniciar servidor em background
start "Laravel Server" php artisan serve --host=127.0.0.1 --port=8000
timeout /t 5 >nul

echo ✓ Servidor iniciado
echo.
echo ═══════════════════════════════════════════════════════════
echo   TESTANDO BOT
echo ═══════════════════════════════════════════════════════════
echo.

echo 5^) Enviando mensagem de teste...
php test_bot_correto.php "Oi"

echo.
echo 6^) Aguardando 20 segundos para processamento...
timeout /t 20 >nul

echo.
echo ═══════════════════════════════════════════════════════════
echo   LOGS FINAIS
echo ═══════════════════════════════════════════════════════════
echo.

REM Mostrar últimas 30 linhas dos logs
powershell -Command "Get-Content 'c:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log' -Tail 30"

echo.
echo Encerrando servidor...
taskkill /F /IM php.exe >nul 2>&1
echo ✓ Concluído!
pause

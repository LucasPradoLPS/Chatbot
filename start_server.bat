@echo off
echo ====================================
echo   SERVIDOR LARAVEL - CHATBOT
echo ====================================
echo.
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo Limpando cache...
php artisan config:clear > nul 2>&1
php artisan cache:clear > nul 2>&1
php artisan optimize:clear > nul 2>&1

echo Iniciando servidor...
echo URL: http://192.168.3.3:8000
echo URL Webhook: http://192.168.3.3:8000/api/webhook/whatsapp
echo.
echo Pressione Ctrl+C para parar
echo.

php artisan serve --host=192.168.3.3 --port=8000

pause

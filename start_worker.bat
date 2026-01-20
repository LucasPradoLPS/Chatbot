@echo off
echo ====================================
echo   INICIANDO WORKER DO CHATBOT
echo ====================================
echo.
echo Worker processando mensagens do WhatsApp...
echo Pressione Ctrl+C para parar
echo.

cd /d "%~dp0"
php artisan queue:work --queue=whatsapp --tries=3 --timeout=300 --sleep=3

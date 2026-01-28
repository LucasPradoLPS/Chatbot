@echo off
echo ====================================
echo   INICIANDO WORKER DO CHATBOT
echo ====================================
echo.
echo Worker processando mensagens do WhatsApp...
echo Pressione Ctrl+C para parar
echo.

cd /d "%~dp0"
php -d max_execution_time=0 artisan queue:work --queue=default,handoff --tries=3 --timeout=300 --sleep=3

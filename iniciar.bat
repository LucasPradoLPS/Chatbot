@echo off
REM Script unificado para rodar servidor Laravel e worker

cd /d "c:\Users\lucas\Downloads\Chatbot-laravel"

echo.
echo ========================================
echo  CHATBOT LARAVEL - INICIALIZADOR
echo ========================================
echo.

echo Limpando processos antigos...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 >nul

echo.
echo Iniciando Laravel Server (porta 8000)...
start "" /B php -d max_execution_time=0 artisan serve --port=8000

timeout /t 5 >nul

echo Iniciando Queue Worker...
start "" /B php -d max_execution_time=0 artisan queue:work --queue=default,handoff --sleep=1 --tries=1 --timeout=120

echo.
echo ========================================
echo Servidor: http://localhost:8000
echo ========================================
echo.
echo Para testar, execute em OUTRO TERMINAL:
echo   php enviar_mensagem.php "Ola"
echo.
echo.

REM Manter a janela aberta
pause

@echo off
setlocal enabledelayedexpansion

if "%~1"=="" (
    echo.
    echo ====================================
    echo   ENVIAR MENSAGEM AO CHATBOT
    echo ====================================
    echo.
    echo Uso:
    echo   enviar_mensagem.bat "sua mensagem aqui"
    echo   enviar_mensagem.bat "Ola, gostaria de informacoes"
    echo   enviar_mensagem.bat "Quero alugar um imovel" 5511988887777
    echo.
    exit /b 1
)

cd /d "%~dp0"
php enviar_mensagem.php %*

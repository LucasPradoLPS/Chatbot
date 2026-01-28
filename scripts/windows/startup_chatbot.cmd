@echo off
REM Auto-start do Chatbot Laravel no login (Startup Folder)
set PROJECT=C:\Users\lucas\Downloads\Chatbot-laravel
REM Inicia o watchdog (ele mantém server+worker sempre vivos)
powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%PROJECT%\scripts\windows\watchdog_chatbot.ps1" -ProjectPath "%PROJECT%" -IntervalSeconds 10

#!/usr/bin/env python3
"""
Script robusto para manter servidor Laravel rodando continuamente
"""
import subprocess
import time
import sys
import os

os.chdir(r"c:\Users\lucas\Downloads\Chatbot-laravel")

PORT = "8000"
HOST = "127.0.0.1"
RESTARTS = 0
MAX_RETRIES = 10

print("=" * 50)
print("  INICIADOR ROBUSTO - LARAVEL SERVER")
print("=" * 50)
print()

while RESTARTS < MAX_RETRIES:
    print(f"[{time.strftime('%H:%M:%S')}] Iniciando servidor Laravel (tentativa {RESTARTS})...")
    
    try:
        process = subprocess.Popen(
            [sys.executable, "-m", "php", "-d", "max_execution_time=600", "artisan", "serve", 
             f"--host={HOST}", f"--port={PORT}"],
            cwd=r"c:\Users\lucas\Downloads\Chatbot-laravel"
        )
        process.wait()
    except Exception as e:
        print(f"❌ Erro: {e}")
    
    RESTARTS += 1
    print(f"⚠️  Servidor caiu. Reiniciando em 3 segundos ({RESTARTS}/{MAX_RETRIES})...")
    time.sleep(3)

print(f"\n❌ Servidor falhou {MAX_RETRIES} vezes. Abortando.")

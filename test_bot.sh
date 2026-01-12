#!/bin/bash

# Script para testar o bot via HTTP

echo "================================"
echo "TESTANDO BOT - ENDPOINTS"
echo "================================"
echo ""

# Teste 1: Ping básico
echo "🔗 Teste 1: Ping básico"
curl -s http://localhost:8000/api/ping
echo ""
echo ""

# Teste 2: Ver logs via API
echo "🔗 Teste 2: Listar logs via API"
curl -s http://localhost:8000/api/debug/logs | head -100
echo ""
echo ""

echo "✅ Testes concluídos!"

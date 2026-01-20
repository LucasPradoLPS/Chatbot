#!/bin/bash
cd "$(dirname "$0")"

echo "===================================="
echo "   SERVIDOR LARAVEL - CHATBOT"
echo "===================================="
echo ""

php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

echo "Iniciando servidor..."
echo "URL: http://192.168.3.3:8000"
echo "URL Webhook: http://192.168.3.3:8000/api/webhook/whatsapp"
echo ""

php artisan serve --host=192.168.3.3 --port=8000

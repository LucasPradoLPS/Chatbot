<?php

echo "═══════════════════════════════════════════════════\n";
echo "🚀 GUIA DE TESTE DO CHATBOT\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "⚠️  IMPORTANTE: O teste anterior falhou porque:\n\n";

echo "❌ O número 5511987654321 NÃO TEM conta WhatsApp na Evolution API\n";
echo "   A Evolution tenta enviar a resposta, mas o número não existe.\n\n";

echo "═══════════════════════════════════════════════════\n";
echo "✅ SOLUÇÕES:\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "1️⃣  USAR SEU PRÓPRIO NÚMERO DE WHATSAPP\n";
echo "   Se você tem WhatsApp em um celular:\n";
echo "   - Abra WhatsApp Web no seu computador\n";
echo "   - Você deve estar na instância N8n da Evolution\n";
echo "   - Depois teste com seu número:\n\n";
echo "   php testar_webhook.php \"Olá\" 55SEUCELULAR\n";
echo "   Exemplo: php testar_webhook.php \"Olá\" 5511987654321\n\n";

echo "2️⃣  USAR NÚMERO DO WHATSAPP WEB (Recomendado)\n";
echo "   - Abra WhatsApp Web (web.whatsapp.com)\n";
echo "   - Seu navegador está logado em uma conta\n";
echo "   - Extraia seu número dessa conta\n";
echo "   - Use esse número no teste\n\n";

echo "3️⃣  VERIFICAR INSTÂNCIA N8N\n";
echo "   - Acesse: http://localhost:8080\n";
echo "   - Verifique se há alguma instância ativa com conta WhatsApp\n";
echo "   - Use o número dessa conta para testar\n\n";

echo "═══════════════════════════════════════════════════\n";
echo "📱 ESTRUTURA DO TESTE:\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "php testar_webhook.php \"MENSAGEM\" NUMERO\n\n";

echo "Exemplos:\n";
echo "  php testar_webhook.php \"Olá, gostaria de informações\" 5511999999999\n";
echo "  php testar_webhook.php \"Quero alugar um imóvel\" 5521988776655\n";
echo "  php testar_webhook.php \"Qual é o seu horário?\" 5585987654321\n\n";

echo "═══════════════════════════════════════════════════\n";
echo "🔧 O QUE ESTÁ FUNCIONANDO:\n";
echo "═══════════════════════════════════════════════════\n\n";

echo "✅ Servidor Laravel rodando em http://192.168.3.3:8000\n";
echo "✅ Webhook recebendo mensagens (HTTP 202)\n";
echo "✅ IA processando e gerando respostas\n";
echo "✅ Evolution API conectada\n\n";

echo "❌ Falta: Número WhatsApp válido na Evolution\n\n";

echo "═══════════════════════════════════════════════════\n";

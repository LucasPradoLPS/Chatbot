# 🚀 GUIA DE TESTE REAL - WhatsApp Bot

## 📋 PRÉ-REQUISITOS VERIFICADOS

✅ **Servidor Laravel rodando** em `http://localhost:8000`
✅ **Bot consolidado** - 1 empresa, 1 agente
✅ **OpenAI configurada** - API Key presente
✅ **Evolution API configurada** - URL e Key presentes
✅ **Banco de dados** - Conectado e funcionando

---

## 🎯 PASSOS PARA TESTE REAL

### 1️⃣ VERIFICAR SEU IP LOCAL

Execute no PowerShell para ver seu IP na rede local:
```powershell
ipconfig | Select-String -Pattern "IPv4"
```

Seu IP será algo como: `192.168.x.x`

### 2️⃣ SERVIDOR ESTÁ RODANDO

O servidor Laravel está rodando e **acessível** em:
- **Local:** http://localhost:8000
- **Rede:** http://SEU_IP:8000 (substitua SEU_IP pelo IP do passo 1)

**Endpoints disponíveis:**
- `POST http://SEU_IP:8000/api/webhook/whatsapp` - Webhook principal
- `GET http://SEU_IP:8000/api/ping` - Teste de conectividade

### 3️⃣ CONFIGURAR WEBHOOK NA EVOLUTION API

Acesse sua **Evolution API** (geralmente em http://localhost:8080 ou sua URL configurada)

#### Opção A: Via Interface Web
1. Acesse o painel da Evolution API
2. Selecione sua instância (provavelmente "N8n")
3. Configure o webhook para:
   ```
   http://SEU_IP:8000/api/webhook/whatsapp
   ```

#### Opção B: Via API (cURL)
Execute no PowerShell (substitua os valores):
```powershell
$evolutionUrl = "http://localhost:8080"  # Sua URL Evolution
$evolutionKey = "VnbFQWPgYUBaLyjXNhJCfQ83WtHZWrHq"  # Sua chave
$instanceName = "N8n"  # Nome da sua instância
$webhookUrl = "http://SEU_IP:8000/api/webhook/whatsapp"

$body = @{
    webhook = @{
        url = $webhookUrl
        webhook_by_events = $true
        events = @("messages.upsert")
    }
} | ConvertTo-Json

Invoke-RestMethod -Uri "$evolutionUrl/webhook/set/$instanceName" -Method Post -Headers @{"apikey"=$evolutionKey} -Body $body -ContentType "application/json"
```

### 4️⃣ ATUALIZAR NOME DA INSTÂNCIA (OPCIONAL)

Se sua instância não se chamar "N8n", atualize no banco:
```bash
php artisan tinker
# No tinker, digite:
App\Models\InstanciaWhatsapp::where('instance_name', 'nome_da_sua_instancia')->update(['instance_name' => 'N8n']);
# Ou crie uma nova:
App\Models\InstanciaWhatsapp::create(['instance_name' => 'SUA_INSTANCIA', 'empresa_id' => 1]);
```

---

## 📱 TESTANDO COM WHATSAPP REAL

### Passo 1: Garantir que a instância está conectada
Verifique se sua instância do WhatsApp na Evolution API está **conectada** (QR Code escaneado).

### Passo 2: Enviar mensagem
**Do seu WhatsApp**, envie uma mensagem para o número conectado na Evolution API:

Exemplos de mensagens para testar:
```
Olá, quero informações sobre apartamentos

Tenho interesse em alugar um imóvel

Quero comprar um apartamento de 2 quartos

Gostaria de agendar uma visita
```

### Passo 3: Monitorar em tempo real
Em outro terminal PowerShell, monitore os logs:
```powershell
Get-Content C:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log -Wait -Tail 20
```

Ou use o comando custom:
```bash
php artisan debug:logs
```

---

## 🔍 VERIFICAÇÕES RÁPIDAS

### Testar se webhook está acessível:
```powershell
# De outro computador na mesma rede:
Invoke-WebRequest "http://SEU_IP:8000/api/ping"

# Deve retornar: {"ok":true,"time":"..."}
```

### Testar webhook manualmente (simulando Evolution):
```powershell
$body = @{
    instance = "N8n"
    data = @{
        key = @{
            remoteJid = "5511999999999@s.whatsapp.net"
            fromMe = $false
            id = "TEST123"
        }
        message = @{
            conversation = "Teste de mensagem real"
        }
        messageTimestamp = [int](Get-Date -UFormat %s)
    }
    event = "messages.upsert"
} | ConvertTo-Json -Depth 5

Invoke-RestMethod -Uri "http://localhost:8000/api/webhook/whatsapp" -Method Post -Body $body -ContentType "application/json"
```

---

## 📊 MONITORAMENTO

### Ver últimas mensagens processadas:
```bash
php artisan debug:logs
```

### Ver threads ativas:
```bash
php artisan test:bot
```

### Limpar logs (se necessário):
```bash
# Backup primeiro
cp storage/logs/laravel.log storage/logs/laravel.log.backup
# Limpar
echo "" > storage/logs/laravel.log
```

---

## ⚠️ TROUBLESHOOTING

### Bot não responde?

**1. Verifique se o webhook foi chamado:**
```bash
Get-Content storage\logs\laravel.log -Tail 50 | Select-String "Webhook received"
```

**2. Verifique erros:**
```bash
Get-Content storage\logs\laravel.log -Tail 50 | Select-String "ERROR"
```

**3. Teste conexão Evolution API:**
```bash
# Verifique se Evolution está respondendo
Invoke-WebRequest "http://localhost:8080"  # ou sua URL
```

**4. Firewall Windows:**
Se não conseguir acessar de outros dispositivos:
```powershell
# Permitir porta 8000
New-NetFirewallRule -DisplayName "Laravel Server" -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow
```

---

## 🎯 FLUXO ESPERADO

```
1. Você envia WhatsApp → Evolution API recebe
                           ↓
2. Evolution API chama → http://SEU_IP:8000/api/webhook/whatsapp
                           ↓
3. Laravel processa → Job ProcessWhatsappMessage
                           ↓
4. OpenAI responde → Thread atualizada
                           ↓
5. Laravel envia → Evolution API → WhatsApp
                           ↓
6. Você recebe resposta no WhatsApp! 🎉
```

---

## 📞 COMANDOS ÚTEIS

```bash
# Rodar servidor (já está rodando)
php artisan serve --host=0.0.0.0 --port=8000

# Testar mensagem (sem WhatsApp)
php artisan bot:testar "sua mensagem aqui"

# Ver status completo
php artisan test:bot

# Ver logs em tempo real
Get-Content storage\logs\laravel.log -Wait -Tail 20

# Limpar cache (se algo não funcionar)
php artisan config:clear
php artisan cache:clear
```

---

## ✅ CHECKLIST ANTES DE TESTAR

- [ ] Servidor Laravel rodando em http://0.0.0.0:8000
- [ ] Evolution API rodando e acessível
- [ ] Instância do WhatsApp conectada (QR Code escaneado)
- [ ] Webhook configurado na Evolution API
- [ ] Nome da instância correto no banco de dados
- [ ] OpenAI API Key válida
- [ ] Firewall não bloqueando porta 8000

---

## 🎉 PRONTO PARA TESTAR!

Agora é só **enviar uma mensagem do seu WhatsApp** para o número conectado na Evolution API e aguardar a resposta do bot!

**Monitore os logs em tempo real com:**
```powershell
Get-Content C:\Users\lucas\Downloads\Chatbot-laravel\storage\logs\laravel.log -Wait -Tail 20
```

Boa sorte! 🚀

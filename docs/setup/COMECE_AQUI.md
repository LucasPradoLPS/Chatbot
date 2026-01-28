# ðŸš€ GUIA DE INÃCIO - Agente de Processamento de MÃ­dia

## Primeiros Passos (5 minutos)

### Passo 1: Instale as DependÃªncias

```bash
cd c:\Users\lucas\Downloads\Chatbot-laravel

# Instale bibliotecas opcionais para PDF/Documentos
composer require spatie/pdf-to-text
composer require phpoffice/phpword
composer require phpoffice/phpspreadsheet
```

> **Nota**: NÃ£o sÃ£o obrigatÃ³rias, mas recomendadas para funcionalidade completa.

### Passo 2: Configure o Arquivo `.env`

Adicione sua chave OpenAI:

```bash
# Abra .env e adicione:
OPENAI_KEY=YOUR_OPENAI_KEY
```

Onde encontrar sua chave:
1. VÃ¡ para https://platform.openai.com/account/api-keys
2. Crie uma nova chave (ou copie uma existente)
3. Cole em `.env`

### Passo 3: Teste Localmente

```bash
# Teste o processamento de mÃ­dia
php test_media_processor.php all
```

VocÃª deve ver algo como:
```
â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
â•‘  ðŸ¤– TESTE DO MEDIA PROCESSOR - AGENTE DE MÃDIA         â•‘
â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

ðŸ“· TESTE 1: PROCESSAMENTO DE IMAGEM
âœ… SUCESSO!
...
```

### Passo 4: Inicie o Servidor (em outro terminal)

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

### Passo 5: Teste com WhatsApp Real (Opcional)

```bash
# Em outro terminal, simule webhook do WhatsApp
php test_media_webhook.php all
```

---

## ðŸ“Š O Que VocÃª Pode Fazer Agora

### Enviar uma Imagem ao Bot
```
VocÃª: [envia foto.jpg ao bot via WhatsApp]

Bot (responde automaticamente):
âœ… Imagem analisada com sucesso!
Aqui estÃ¡ o que identifiquei:
â€¢ Sala moderna 4x5m
â€¢ SofÃ¡ cinza
â€¢ IluminaÃ§Ã£o natural
Como posso ajudÃ¡-lo?
```

### Enviar um PDF ao Bot
```
VocÃª: [envia contrato.pdf ao bot]

Bot:
âœ… PDF processado com sucesso!
Identifiquei:
â€¢ Valor: R$ 650.000
â€¢ Local: Morumbi, SP
â€¢ Pagamento: 50% entrada
Gostaria de anÃ¡lise?
```

### Enviar uma Planilha ao Bot
```
VocÃª: [envia imoveis.csv ao bot]

Bot:
âœ… Documento processado!
Vi sua planilha com 15 imÃ³veis listados.
Posso ajudÃ¡-lo a filtrar por:
ðŸ˜ï¸  Bairro
ðŸ’° Valor
ðŸ  Quartos
```

---

## ðŸ› ï¸ Comandos Ãšteis

### Processar arquivo local
```bash
php artisan media:process /caminho/para/imagem.jpg
php artisan media:process /caminho/para/documento.pdf --type=pdf
```

### Limpar arquivos antigos
```bash
# Remove arquivos com mais de 30 dias
php artisan media:cleanup --days=30

# Remove arquivos com mais de 7 dias
php artisan media:cleanup --days=7
```

### Verificar logs
```bash
# Ver Ãºltimos logs em tempo real
tail -f storage/logs/laravel.log

# Ou no Windows (PowerShell)
Get-Content storage/logs/laravel.log -Tail 50 -Wait
```

### Limpar arquivos armazenados
```bash
# Ver arquivos processados
ls -la storage/app/public/whatsapp_media/

# Deletar pasta (se necessÃ¡rio)
rm -r storage/app/public/whatsapp_media/
```

---

## âœ… Checklist de Setup

- [ ] Instalar dependÃªncias: `composer require spatie/pdf-to-text`
- [ ] Configurar OPENAI_KEY em `.env`
- [ ] Rodar teste: `php test_media_processor.php all`
- [ ] Verificar pasta criada: `storage/app/public/whatsapp_media/`
- [ ] Iniciar servidor: `php artisan serve`
- [ ] Enviar arquivo ao WhatsApp para testar
- [ ] Verificar resposta no bot
- [ ] Consultar logs: `storage/logs/laravel.log`

---

## ðŸŽ¯ O Que Foi Criado

### ðŸ“‚ Arquivos Novos
```
app/Services/MediaProcessor.php
app/Console/Commands/ProcessMediaCommand.php
app/Console/Commands/CleanupMediaCommand.php
storage/app/public/whatsapp_media/ (pasta)
```

### ðŸ“ ModificaÃ§Ãµes
```
app/Jobs/ProcessWhatsappMessage.php
  - Adicionado: import MediaProcessor
  - Adicionado: mÃ©todo processarMedia()
  - Adicionado: mÃ©todo montarRespostaMedia()
```

### ðŸ“š DocumentaÃ§Ã£o
```
MEDIA_PROCESSOR_README.md             â† Comece por aqui
MEDIA_PROCESSOR_GUIA.md               â† DocumentaÃ§Ã£o completa
MEDIA_PROCESSOR_FLUXO.md              â† Diagramas
MEDIA_PROCESSOR_CONFIG.md             â† ConfiguraÃ§Ã£o avanÃ§ada
MEDIA_PROCESSOR_IMPLEMENTACAO_COMPLETA.md
test_media_processor.php              â† Script de teste
test_media_webhook.php                â† Teste webhook
```

---

## ðŸ” Como Verificar Que EstÃ¡ Funcionando

### 1. Verificar Syntax
```bash
php -l app/Services/MediaProcessor.php
# Deve retornar: No syntax errors detected
```

### 2. Verificar Arquivo Criado
```bash
ls app/Services/MediaProcessor.php
# Deve mostrar: app/Services/MediaProcessor.php
```

### 3. Verificar IntegraÃ§Ã£o
```bash
grep "MediaProcessor" app/Jobs/ProcessWhatsappMessage.php
# Deve mostrar vÃ¡rias linhas com MediaProcessor
```

### 4. Testar Procesamento
```bash
php test_media_processor.php image
# Deve processar imagem e mostrar resultado
```

### 5. Verificar Armazenamento
```bash
ls storage/app/public/whatsapp_media/
# Deve mostrar pasta com imagens/documentos processados
```

---

## ðŸ› Problemas Comuns

### "OPENAI_KEY nÃ£o configurada"
**SoluÃ§Ã£o**: Adicione em `.env`:
```env
OPENAI_KEY=YOUR_OPENAI_KEY
```

### "Classe MediaProcessor nÃ£o encontrada"
**SoluÃ§Ã£o**: Execute:
```bash
composer dump-autoload
```

### "spatie/pdf-to-text nÃ£o encontrado"
**SoluÃ§Ã£o**: Instale:
```bash
composer require spatie/pdf-to-text
```

### "Arquivo muito grande"
**SoluÃ§Ã£o**: 
- Reduza tamanho do arquivo (mÃ¡x 50MB)
- Ou edite `MediaProcessor.php` linha 17:
```php
private $maxFileSize = 100 * 1024 * 1024; // 100MB
```

### "Timeout ao processar"
**SoluÃ§Ã£o**: Aumente timeout em `MediaProcessor.php`:
```php
Http::timeout(60)->get($url);  // Era 30, agora 60
```

---

## ðŸ“š Leitura Recomendada

1. **Comece aqui**: [MEDIA_PROCESSOR_README.md](./MEDIA_PROCESSOR_README.md)
   - 5 minutos de leitura
   - VisÃ£o geral das funcionalidades
   - Exemplos simples

2. **Guia Completo**: [MEDIA_PROCESSOR_GUIA.md](./MEDIA_PROCESSOR_GUIA.md)
   - 15-20 minutos
   - DocumentaÃ§Ã£o tÃ©cnica
   - Todos os parÃ¢metros

3. **Diagramas**: [MEDIA_PROCESSOR_FLUXO.md](./MEDIA_PROCESSOR_FLUXO.md)
   - 10 minutos
   - Visualizar arquitetura
   - Entender fluxo

4. **ConfiguraÃ§Ã£o**: [MEDIA_PROCESSOR_CONFIG.md](./MEDIA_PROCESSOR_CONFIG.md)
   - 10-15 minutos
   - CustomizaÃ§Ãµes avanÃ§adas
   - IntegraÃ§Ãµes

---

## ðŸš€ PrÃ³ximas AÃ§Ãµes

### Hoje
- [x] Ler este guia
- [x] Instalar dependÃªncias
- [x] Configurar `.env`
- [x] Rodar testes

### Esta Semana
- [ ] Enviar arquivo real ao WhatsApp
- [ ] Validar resposta do bot
- [ ] Agendar limpeza automÃ¡tica

### Este MÃªs
- [ ] Customizar prompts
- [ ] Adicionar monitoramento
- [ ] Integrar com outros sistemas

---

## ðŸ“ž Suporte

### DocumentaÃ§Ã£o
- README: [MEDIA_PROCESSOR_README.md](./MEDIA_PROCESSOR_README.md)
- Guia: [MEDIA_PROCESSOR_GUIA.md](./MEDIA_PROCESSOR_GUIA.md)
- CÃ³digo: [app/Services/MediaProcessor.php](./app/Services/MediaProcessor.php)

### Logs
- Verificar: `storage/logs/laravel.log`
- Buscar por: "MIDIA PROCESSADA" ou "MediaProcessor"

### Arquivos Processados
- LocalizaÃ§Ã£o: `storage/app/public/whatsapp_media/`
- Estrutura:
  - `images/` - Imagens processadas
  - `documents/` - PDFs e documentos
  - `audio/` - Arquivos de Ã¡udio

---

## âœ¨ Resultado Final

VocÃª agora tem um **agente inteligente** que:

âœ… Processa imagens com OpenAI Vision  
âœ… Extrai texto de PDFs automaticamente  
âœ… Processa documentos (DOCX, CSV, TXT)  
âœ… Armazena arquivos seguramente  
âœ… Responde contextualizado ao usuÃ¡rio  
âœ… MantÃ©m histÃ³rico de interaÃ§Ãµes  
âœ… Trata erros elegantemente  
âœ… Pronto para produÃ§Ã£o  

---

## ðŸŽ‰ ParabÃ©ns!

VocÃª agora tem um sistema profissional de processamento de mÃ­dia integrado ao seu chatbot!

**PrÃ³ximo passo**: Envie um arquivo ao seu bot via WhatsApp e veja a mÃ¡gica acontecer! âœ¨

---

**VersÃ£o**: 1.0.0  
**Data**: 16/01/2025  
**Status**: âœ… Pronto para usar  



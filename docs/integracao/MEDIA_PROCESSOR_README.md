# ðŸ¤– Agente de Processamento de MÃ­dia - InÃ­cio RÃ¡pido

## O que foi criado?

Um **agente inteligente em PHP** que processa imagens, PDFs, documentos e Ã¡udio enviados via WhatsApp atravÃ©s do seu chatbot Laravel.

### âœ¨ Funcionalidades

| Tipo | Processamento | Resultado |
|------|--------------|-----------|
| ðŸ“· **Imagem** | OpenAI Vision (GPT-4) | DescriÃ§Ã£o detalhada do conteÃºdo visual |
| ðŸ“„ **PDF** | ExtraÃ§Ã£o de texto (spatie/pdf-to-text) | Texto completo do documento |
| ðŸ“Š **Documento** | Parse XML/CSV | ConteÃºdo estruturado |
| ðŸŽ™ï¸ **Ãudio** | Armazenamento (Whisper futuro) | Arquivo salvo localmente |

---

## âš¡ InÃ­cio RÃ¡pido

### 1. **Instalar Bibliotecas**

```bash
composer require spatie/pdf-to-text
composer require phpoffice/phpword      # DOCX
composer require phpoffice/phpspreadsheet # XLSX
```

### 2. **Configurar `.env`**

```env
OPENAI_KEY=YOUR_OPENAI_KEY
```

### 3. **Testar Localmente**

```bash
php test_media_processor.php all
```

### 4. **Usar com WhatsApp**

Simplesmente envie uma imagem ou PDF ao bot:

```
VocÃª: [envia foto.jpg]
Bot: âœ… Imagem analisada com sucesso!
     Vejo uma sala moderna com...
     [descriÃ§Ã£o detalhada]
```

---

## ðŸ“ Arquivos Criados

```
app/Services/
â”œâ”€â”€ MediaProcessor.php          â† ServiÃ§o principal de processamento

app/Jobs/
â”œâ”€â”€ ProcessWhatsappMessage.php  â† Modificado para integrar MediaProcessor
    â”œâ”€â”€ processarMedia()        â† Novo mÃ©todo
    â””â”€â”€ montarRespostaMedia()   â† Novo mÃ©todo

app/Console/Commands/
â”œâ”€â”€ ProcessMediaCommand.php     â† CLI para processar arquivo local
â””â”€â”€ CleanupMediaCommand.php     â† CLI para limpar arquivos antigos

DocumentaÃ§Ã£o/
â”œâ”€â”€ MEDIA_PROCESSOR_GUIA.md     â† Guia completo
â”œâ”€â”€ MEDIA_PROCESSOR_FLUXO.md    â† Diagramas de arquitetura
â””â”€â”€ test_media_processor.php    â† Script de teste
```

---

## ðŸ—ï¸ Arquitetura

```
WhatsApp â†’ Evolution API â†’ ProcessWhatsappMessage
                              â†“
                       MediaProcessor
                       â”œâ”€ Imagem â†’ OpenAI Vision
                       â”œâ”€ PDF â†’ spatie/pdf-to-text
                       â”œâ”€ Documento â†’ Parse
                       â””â”€ Ãudio â†’ Armazenar
                              â†“
                       Resposta contextualizada
                              â†“
                          Evolution â†’ WhatsApp
```

---

## ðŸ’¡ Exemplos de Uso

### Exemplo 1: AnÃ¡lise de ImÃ³vel
```
User: [envia foto do apartamento]
Bot:  âœ… Imagem analisada com sucesso!
      
      Aqui estÃ¡ o que identifiquei:
      â€¢ Sala ampla com 3 janelas
      â€¢ Piso em madeira clara
      â€¢ IluminaÃ§Ã£o natural excelente
      â€¢ DecoraÃ§Ã£o contemporÃ¢nea
      
      Gostaria de saber o preÃ§o ou localizaÃ§Ã£o?
```

### Exemplo 2: Processamento de Contrato
```
User: [envia contrato.pdf]
Bot:  âœ… PDF processado com sucesso!
      
      Identifiquei:
      â€¢ Valor: R$ 650.000
      â€¢ LocalizaÃ§Ã£o: Morumbi, SP
      â€¢ Pagamento: 50% entrada
      â€¢ Prazo: 360 meses
      
      Gostaria de anÃ¡lise financeira?
```

### Exemplo 3: Processamento de Planilha
```
User: [envia dados.xlsx]
Bot:  âœ… Documento processado!
      
      Vejo uma planilha com 15 imÃ³veis listados.
      Posso ajudÃ¡-lo a filtrar por:
      â€¢ Bairro (Morumbi, Vila Mariana, etc)
      â€¢ Valor (atÃ© 500k, 500-800k, 800k+)
      â€¢ Quartos (2, 3, 4+)
```

---

## ðŸ”§ Comandos Artisan

### Processar arquivo local
```bash
php artisan media:process /caminho/para/imagem.jpg
php artisan media:process /caminho/para/documento.pdf --type=pdf
```

### Limpar arquivos antigos
```bash
php artisan media:cleanup --days=30    # Remove antigos que 30 dias
php artisan media:cleanup --days=7     # Remove mais antigos que 7 dias
```

### Adicionar ao agendador (Scheduler)
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('media:cleanup --days=30')->daily();
}
```

---

## ðŸ“Š Estrutura de Armazenamento

```
storage/app/public/whatsapp_media/
â”œâ”€â”€ images/
â”‚   â”œâ”€â”€ img_657a3b1c.jpg
â”‚   â”œâ”€â”€ img_657a3b2d.png
â”‚   â””â”€â”€ ...
â”œâ”€â”€ documents/
â”‚   â”œâ”€â”€ doc_657a3c1f.pdf
â”‚   â”œâ”€â”€ doc_657a3c2g.docx
â”‚   â””â”€â”€ ...
â””â”€â”€ audio/
    â”œâ”€â”€ audio_657a3d1j.ogg
    â””â”€â”€ ...
```

---

## ðŸš€ Fluxo Completo

1. **UsuÃ¡rio envia arquivo** via WhatsApp
2. **Evolution API recebe** e dispara webhook
3. **ProcessWhatsappMessage** detecta tipo de mÃ­dia
4. **MediaProcessor** Ã© acionado:
   - Valida tipo e tamanho
   - Baixa arquivo
   - Processa conforme tipo
   - Extrai conteÃºdo
   - Armazena localmente
5. **Thread Ã© atualizado** com histÃ³rico
6. **Resposta contextualizada** Ã© montada
7. **Evolution envia resposta** ao WhatsApp
8. **UsuÃ¡rio recebe** anÃ¡lise completa

---

## âš™ï¸ ConfiguraÃ§Ãµes

### Limites
```php
private $maxFileSize = 50 * 1024 * 1024; // 50MB
```

### Armazenamento
```php
private $mediaDisk = 'public';           // Disco Laravel
private $mediaPath = 'whatsapp_media';   // Pasta relativa
```

### Timeouts
```php
Http::timeout(30)->get($url);  // 30 segundos para download
```

---

## ðŸ› Troubleshooting

| Problema | Causa | SoluÃ§Ã£o |
|----------|-------|---------|
| "Imagem nÃ£o pode ser analisada" | OPENAI_KEY nÃ£o configurada | Adicionar em `.env` |
| "PDF recebido mas sem texto" | spatie/pdf-to-text nÃ£o instalado | `composer require spatie/pdf-to-text` |
| Timeout ao processar | Arquivo muito grande | Aumentar timeout ou reduzir tamanho |
| Arquivos acumulando | Sem limpeza automÃ¡tica | Agendar `media:cleanup` no Scheduler |

---

## ðŸ”® Roadmap

- [x] Processamento de imagens com OpenAI Vision
- [x] ExtraÃ§Ã£o de texto de PDFs
- [x] Armazenamento seguro de arquivos
- [ ] TranscriÃ§Ã£o de Ã¡udio (Whisper API)
- [ ] OCR em imagens (Tesseract)
- [ ] AnÃ¡lise de documentos (Claude)
- [ ] Cache de anÃ¡lises (Redis)
- [ ] Processamento em background (fila)

---

## ðŸ“š DocumentaÃ§Ã£o

- **Guia Completo**: [MEDIA_PROCESSOR_GUIA.md](./MEDIA_PROCESSOR_GUIA.md)
- **Fluxos Visuais**: [MEDIA_PROCESSOR_FLUXO.md](./MEDIA_PROCESSOR_FLUXO.md)
- **ImplementaÃ§Ã£o**: [app/Services/MediaProcessor.php](./app/Services/MediaProcessor.php)

---

## âœ… Checklist de ImplementaÃ§Ã£o

- [x] Criar serviÃ§o MediaProcessor
- [x] Integrar em ProcessWhatsappMessage
- [x] Suporte para imagens (OpenAI Vision)
- [x] Suporte para PDFs (spatie/pdf-to-text)
- [x] Suporte para documentos (DOCX, CSV, TXT)
- [x] Armazenamento seguro com UUID
- [x] Logging estruturado
- [x] Tratamento de erros
- [x] Comandos Artisan
- [x] DocumentaÃ§Ã£o completa
- [x] Script de teste
- [ ] Testes unitÃ¡rios
- [ ] IntegraÃ§Ã£o com Whisper (Ã¡udio)
- [ ] Cache de anÃ¡lises

---

## ðŸ“ž PrÃ³ximos Passos

1. **Instalar dependÃªncias opcionais** conforme necessÃ¡rio
2. **Testar com `test_media_processor.php`**
3. **Enviar arquivo real ao WhatsApp** e verificar resposta
4. **Monitorar logs** em `storage/logs/laravel.log`
5. **Agendar limpeza** de arquivos antigos
6. **Expandir conforme feedback** dos usuÃ¡rios

---

**Ãšltima atualizaÃ§Ã£o**: 16/01/2025
**VersÃ£o**: 1.0.0
**Status**: âœ… Pronto para produÃ§Ã£o


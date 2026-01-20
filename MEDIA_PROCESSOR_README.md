# 🤖 Agente de Processamento de Mídia - Início Rápido

## O que foi criado?

Um **agente inteligente em PHP** que processa imagens, PDFs, documentos e áudio enviados via WhatsApp através do seu chatbot Laravel.

### ✨ Funcionalidades

| Tipo | Processamento | Resultado |
|------|--------------|-----------|
| 📷 **Imagem** | OpenAI Vision (GPT-4) | Descrição detalhada do conteúdo visual |
| 📄 **PDF** | Extração de texto (spatie/pdf-to-text) | Texto completo do documento |
| 📊 **Documento** | Parse XML/CSV | Conteúdo estruturado |
| 🎙️ **Áudio** | Armazenamento (Whisper futuro) | Arquivo salvo localmente |

---

## ⚡ Início Rápido

### 1. **Instalar Bibliotecas**

```bash
composer require spatie/pdf-to-text
composer require phpoffice/phpword      # DOCX
composer require phpoffice/phpspreadsheet # XLSX
```

### 2. **Configurar `.env`**

```env
OPENAI_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx
```

### 3. **Testar Localmente**

```bash
php test_media_processor.php all
```

### 4. **Usar com WhatsApp**

Simplesmente envie uma imagem ou PDF ao bot:

```
Você: [envia foto.jpg]
Bot: ✅ Imagem analisada com sucesso!
     Vejo uma sala moderna com...
     [descrição detalhada]
```

---

## 📁 Arquivos Criados

```
app/Services/
├── MediaProcessor.php          ← Serviço principal de processamento

app/Jobs/
├── ProcessWhatsappMessage.php  ← Modificado para integrar MediaProcessor
    ├── processarMedia()        ← Novo método
    └── montarRespostaMedia()   ← Novo método

app/Console/Commands/
├── ProcessMediaCommand.php     ← CLI para processar arquivo local
└── CleanupMediaCommand.php     ← CLI para limpar arquivos antigos

Documentação/
├── MEDIA_PROCESSOR_GUIA.md     ← Guia completo
├── MEDIA_PROCESSOR_FLUXO.md    ← Diagramas de arquitetura
└── test_media_processor.php    ← Script de teste
```

---

## 🏗️ Arquitetura

```
WhatsApp → Evolution API → ProcessWhatsappMessage
                              ↓
                       MediaProcessor
                       ├─ Imagem → OpenAI Vision
                       ├─ PDF → spatie/pdf-to-text
                       ├─ Documento → Parse
                       └─ Áudio → Armazenar
                              ↓
                       Resposta contextualizada
                              ↓
                          Evolution → WhatsApp
```

---

## 💡 Exemplos de Uso

### Exemplo 1: Análise de Imóvel
```
User: [envia foto do apartamento]
Bot:  ✅ Imagem analisada com sucesso!
      
      Aqui está o que identifiquei:
      • Sala ampla com 3 janelas
      • Piso em madeira clara
      • Iluminação natural excelente
      • Decoração contemporânea
      
      Gostaria de saber o preço ou localização?
```

### Exemplo 2: Processamento de Contrato
```
User: [envia contrato.pdf]
Bot:  ✅ PDF processado com sucesso!
      
      Identifiquei:
      • Valor: R$ 650.000
      • Localização: Morumbi, SP
      • Pagamento: 50% entrada
      • Prazo: 360 meses
      
      Gostaria de análise financeira?
```

### Exemplo 3: Processamento de Planilha
```
User: [envia dados.xlsx]
Bot:  ✅ Documento processado!
      
      Vejo uma planilha com 15 imóveis listados.
      Posso ajudá-lo a filtrar por:
      • Bairro (Morumbi, Vila Mariana, etc)
      • Valor (até 500k, 500-800k, 800k+)
      • Quartos (2, 3, 4+)
```

---

## 🔧 Comandos Artisan

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

## 📊 Estrutura de Armazenamento

```
storage/app/public/whatsapp_media/
├── images/
│   ├── img_657a3b1c.jpg
│   ├── img_657a3b2d.png
│   └── ...
├── documents/
│   ├── doc_657a3c1f.pdf
│   ├── doc_657a3c2g.docx
│   └── ...
└── audio/
    ├── audio_657a3d1j.ogg
    └── ...
```

---

## 🚀 Fluxo Completo

1. **Usuário envia arquivo** via WhatsApp
2. **Evolution API recebe** e dispara webhook
3. **ProcessWhatsappMessage** detecta tipo de mídia
4. **MediaProcessor** é acionado:
   - Valida tipo e tamanho
   - Baixa arquivo
   - Processa conforme tipo
   - Extrai conteúdo
   - Armazena localmente
5. **Thread é atualizado** com histórico
6. **Resposta contextualizada** é montada
7. **Evolution envia resposta** ao WhatsApp
8. **Usuário recebe** análise completa

---

## ⚙️ Configurações

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

## 🐛 Troubleshooting

| Problema | Causa | Solução |
|----------|-------|---------|
| "Imagem não pode ser analisada" | OPENAI_KEY não configurada | Adicionar em `.env` |
| "PDF recebido mas sem texto" | spatie/pdf-to-text não instalado | `composer require spatie/pdf-to-text` |
| Timeout ao processar | Arquivo muito grande | Aumentar timeout ou reduzir tamanho |
| Arquivos acumulando | Sem limpeza automática | Agendar `media:cleanup` no Scheduler |

---

## 🔮 Roadmap

- [x] Processamento de imagens com OpenAI Vision
- [x] Extração de texto de PDFs
- [x] Armazenamento seguro de arquivos
- [ ] Transcrição de áudio (Whisper API)
- [ ] OCR em imagens (Tesseract)
- [ ] Análise de documentos (Claude)
- [ ] Cache de análises (Redis)
- [ ] Processamento em background (fila)

---

## 📚 Documentação

- **Guia Completo**: [MEDIA_PROCESSOR_GUIA.md](./MEDIA_PROCESSOR_GUIA.md)
- **Fluxos Visuais**: [MEDIA_PROCESSOR_FLUXO.md](./MEDIA_PROCESSOR_FLUXO.md)
- **Implementação**: [app/Services/MediaProcessor.php](./app/Services/MediaProcessor.php)

---

## ✅ Checklist de Implementação

- [x] Criar serviço MediaProcessor
- [x] Integrar em ProcessWhatsappMessage
- [x] Suporte para imagens (OpenAI Vision)
- [x] Suporte para PDFs (spatie/pdf-to-text)
- [x] Suporte para documentos (DOCX, CSV, TXT)
- [x] Armazenamento seguro com UUID
- [x] Logging estruturado
- [x] Tratamento de erros
- [x] Comandos Artisan
- [x] Documentação completa
- [x] Script de teste
- [ ] Testes unitários
- [ ] Integração com Whisper (áudio)
- [ ] Cache de análises

---

## 📞 Próximos Passos

1. **Instalar dependências opcionais** conforme necessário
2. **Testar com `test_media_processor.php`**
3. **Enviar arquivo real ao WhatsApp** e verificar resposta
4. **Monitorar logs** em `storage/logs/laravel.log`
5. **Agendar limpeza** de arquivos antigos
6. **Expandir conforme feedback** dos usuários

---

**Última atualização**: 16/01/2025
**Versão**: 1.0.0
**Status**: ✅ Pronto para produção

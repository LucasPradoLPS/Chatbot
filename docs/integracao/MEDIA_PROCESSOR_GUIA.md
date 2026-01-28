# ðŸ¤– Agente de Processamento de MÃ­dia - DocumentaÃ§Ã£o Completa

## VisÃ£o Geral

O **MediaProcessor** Ã© um agente PHP inteligente que processa imagens, PDFs, documentos e Ã¡udio enviados via WhatsApp. Integra-se com OpenAI Vision para anÃ¡lise de conteÃºdo visual e extrai texto de documentos automaticamente.

## ðŸ“‹ Funcionalidades

### âœ… Tipos de Arquivo Suportados

#### **Imagens**
- JPEG, PNG, GIF, WebP
- **Processamento**: AnÃ¡lise com OpenAI Vision API
- **Output**: DescriÃ§Ã£o detalhada do conteÃºdo visual
- **Exemplo**: UsuÃ¡rio envia foto de imÃ³vel â†’ Bot descreve caracterÃ­sticas visuais

#### **PDFs**
- Formato: application/pdf
- **Processamento**: ExtraÃ§Ã£o de texto com pdftotext (spatie/pdf-to-text)
- **Output**: Texto extraÃ­do ou resumo do conteÃºdo
- **Exemplo**: UsuÃ¡rio envia contrato â†’ Bot extrai e analisa termos

#### **Documentos**
- DOCX (Word), XLSX (Excel), CSV, TXT
- **Processamento**: ExtraÃ§Ã£o de texto baseado no tipo
- **Output**: ConteÃºdo estruturado e interpretÃ¡vel
- **Exemplo**: UsuÃ¡rio envia planilha de orÃ§amento â†’ Bot analisa nÃºmeros

#### **Ãudio**
- OGG, MP3
- **Processamento**: Armazenamento e interface para Whisper API (futuro)
- **Output**: InformaÃ§Ã£o de recebimento
- **Exemplo**: UsuÃ¡rio envia mensagem de voz com contexto longo

#### **VÃ­deo**
- Status: NÃ£o suportado (requer ffmpeg/conversÃ£o)
- **Fallback**: Mensagem pedindo ao usuÃ¡rio descrever em texto

---

## ðŸ—ï¸ Arquitetura

### Fluxo de Processamento

```
WhatsApp (Evolution API)
         â†“
    [Webhook recebe]
         â†“
ProcessWhatsappMessage.php
         â†“
    [Detecta tipo de mÃ­dia]
         â†“
    processarMedia() method
         â†“
MediaProcessor::processar()
         â†“
  [Valida tipo/tamanho]
         â†“
  [Download do arquivo]
         â†“
[Processamento especÃ­fico]
  â”œâ”€ Imagem â†’ OpenAI Vision
  â”œâ”€ PDF â†’ ExtraÃ§Ã£o de texto
  â”œâ”€ Documento â†’ Parse XML/CSV
  â””â”€ Ãudio â†’ Armazenamento
         â†“
[Armazenamento local]
         â†“
[Resposta contextualizada]
         â†“
Evolution API (WhatsApp)
```

### Componentes Principais

#### 1. **MediaProcessor** (`app/Services/MediaProcessor.php`)
- ServiÃ§o central de processamento
- MÃ©todos pÃºblicos:
  - `processar(array $msgData): array` - Processa qualquer tipo de mÃ­dia
  - `limparArquivosAntigos(int $diasRetencao): array` - Remove arquivos antigos

#### 2. **ProcessWhatsappMessage** (integraÃ§Ã£o)
- Importa: `use App\Services\MediaProcessor;`
- Chama: `processarMedia($tipoMensagem, $msgData, ...)`
- MÃ©todos adicionados:
  - `processarMedia()` - Orquestra processamento
  - `montarRespostaMedia()` - Cria resposta contextualizada

#### 3. **Comandos Artisan**
- `php artisan media:process {file} --type=auto` - Processa arquivo local
- `php artisan media:cleanup --days=30` - Remove arquivos antigos

---

## ðŸ’» Como Usar

### 1. **Processamento AutomÃ¡tico (via WhatsApp)**

UsuÃ¡rio envia uma imagem para o bot:

```
UsuÃ¡rio: [envia imagem de imÃ³vel]
Bot: âœ… Imagem analisada com sucesso!

Aqui estÃ¡ o que identifiquei:

ðŸ“· **AnÃ¡lise de Imagem:**
A imagem mostra uma sala de estar moderna com...
- Movedores de sofÃ¡ cinza em estilo minimalista
- Piso em madeira clara
- IluminaÃ§Ã£o natural por janelas amplas
- DecoraÃ§Ã£o contemporÃ¢nea

Como posso ajudÃ¡-lo com relaÃ§Ã£o a isso? ðŸ¤”
```

### 2. **PDF com Contrato**

```
UsuÃ¡rio: [envia PDF de contrato]
Bot: âœ… PDF processado com sucesso!

**ConteÃºdo extraÃ­do:**

CONTRATO DE COMPRA E VENDA
Partes: Vendedor: JoÃ£o Silva
        Comprador: Maria Santos
        
Objeto: ImÃ³vel localizado em SÃ£o Paulo, SP
        Bairro: Morumbi
        
Valor: R$ 650.000,00
Forma de pagamento: 50% na assinatura...

...(conteÃºdo truncado)

Como posso ajudar com este documento? ðŸ“„
```

### 3. **Comando Manual (Desenvolvimento)**

```bash
# Processa arquivo e exibe resultado
php artisan media:process "/caminho/para/imagem.jpg"

# Limpa arquivos com mais de 30 dias
php artisan media:cleanup --days=30

# Limpa arquivos antigos (customizado)
php artisan media:cleanup --days=7
```

---

## ðŸ”§ ConfiguraÃ§Ã£o

### PrÃ©-requisitos

1. **OpenAI API Key** (em `.env`):
```env
OPENAI_KEY=YOUR_OPENAI_KEY
```

2. **Armazenamento de Arquivos**:
- Disco `public` deve estar configurado (padrÃ£o Laravel)
- Pasta `storage/app/public/whatsapp_media` serÃ¡ criada automaticamente

3. **Bibliotecas Opcionais** (para PDFs):
```bash
composer require spatie/pdf-to-text
composer require phpoffice/phpword  # Para DOCX
composer require phpoffice/phpspreadsheet  # Para XLSX
```

### Limites de Tamanho

```php
private $maxFileSize = 50 * 1024 * 1024; // 50MB
```

Ajustar em `MediaProcessor.php` conforme necessÃ¡rio.

---

## ðŸ“ Exemplo de ImplementaÃ§Ã£o

### Estrutura de Resposta

Quando processamento **bem-sucedido**:

```php
[
    'success' => true,
    'tipo_midia' => 'image',  // image|pdf|document|audio
    'conteudo_extraido' => 'DescriÃ§Ã£o detalhada do arquivo...',
    'arquivo_local' => 'whatsapp_media/images/img_123456.jpg',
    'metadados' => [
        'tamanho_bytes' => 245632,
        'mime_type' => 'image/jpeg',
        'url_original' => 'https://...',
        'nome_original' => 'foto.jpg'  // Para documentos
    ]
]
```

Quando **falha**:

```php
[
    'success' => false,
    'tipo_midia' => 'image',
    'erro' => 'URL da imagem nÃ£o fornecida'
]
```

### Respostas Customizadas

Editar mÃ©todo `montarRespostaMedia()` em `ProcessWhatsappMessage.php`:

```php
private function montarRespostaMedia(string $tipoMidia, string $conteudo, Thread $thread): string
{
    match ($tipoMidia) {
        'image' => "Custom response for images...",
        'pdf' => "Custom response for PDFs...",
        // ...
    };
}
```

---

## ðŸš€ Recursos AvanÃ§ados

### 1. **OpenAI Vision para Imagens**

O agente usa `gpt-4o-mini` para anÃ¡lise visual:

```php
private function analisarImagemComOpenAI(string $imageUrl): string
{
    // Envia imagem para GPT-4 Vision
    // Retorna anÃ¡lise estruturada do conteÃºdo
}
```

**Customizar prompt**: Editar em `MediaProcessor.php` linha ~270

### 2. **ExtraÃ§Ã£o de Texto de PDFs**

Usa biblioteca Spatie:

```php
private function extrairTextoPDF(string $conteudoPDF): string
{
    $texto = (new \Spatie\PdfToText\Pdf($tempFile))->text();
    return trim($texto);
}
```

Se nÃ£o instalado, retorna informaÃ§Ã£o genÃ©rica.

### 3. **Processamento de Documentos DOCX**

Extrai XML interno do arquivo ZIP:

```php
if ($mimetype === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
    // Remove tags XML, preserva apenas texto
    $texto = preg_replace('/<[^>]*>/', ' ', $xmlContent);
}
```

### 4. **IntegraÃ§Ã£o com Estado do Thread**

Armazena metadados no histÃ³rico:

```php
$historico[] = [
    'timestamp' => now()->toIso8601String(),
    'tipo' => 'midia_processada',
    'tipo_midia' => $tipoMidia,
    'arquivo_local' => $caminhoLocal,
    'conteudo_chars' => strlen($conteudo),
    'metadados' => [...]  // Detalhes do arquivo
];

$thread->update(['estado_historico' => $historico]);
```

---

## ðŸ›¡ï¸ ValidaÃ§Ãµes & SeguranÃ§a

### ValidaÃ§Ãµes Implementadas

1. **Tipo de Arquivo**
   - Whitelist de MIME types suportados
   - Rejeita tipos nÃ£o reconhecidos

2. **Tamanho do Arquivo**
   - MÃ¡ximo 50MB (configurÃ¡vel)
   - Previne consumo excessivo de recursos

3. **Tipos de MIME**
   - Verifica antes de processar
   - Diferencia entre imagem, PDF, documento, Ã¡udio

4. **Tratamento de Erros**
   - Try-catch em mÃºltiplos nÃ­veis
   - Logs detalhados de falhas
   - Respostas amigÃ¡veis ao usuÃ¡rio

### RecomendaÃ§Ãµes de SeguranÃ§a

```env
# .env
MEDIA_MAX_FILE_SIZE=52428800  # 50MB em bytes
MEDIA_RETENTION_DAYS=30       # Deletar arquivos antigos
MEDIA_STORAGE_DISK=public     # Disco seguro
```

### Armazenamento Seguro

```php
// Arquivos armazenados em:
storage/app/public/whatsapp_media/
â”œâ”€â”€ images/        # Imagens processadas
â”œâ”€â”€ documents/     # PDFs e documentos
â”œâ”€â”€ audio/         # Arquivos de Ã¡udio
â””â”€â”€ [uuid_arquivo] # Nomes Ãºnicos (evita colisÃ£o)
```

---

## ðŸ“Š Exemplos de Casos de Uso

### Caso 1: AnÃ¡lise de ImÃ³vel via Foto
```
UsuÃ¡rio: [envia foto de apartamento]
         â†“
Bot analisa com Vision API
         â†“
Bot: "Vejo uma sala ampla com 3 janelas, piso em porcelanato,
     paredes brancas e iluminaÃ§Ã£o natural excelente. 
     Gostaria de saber mais sobre localizaÃ§Ã£o ou preÃ§o?"
```

### Caso 2: Documento de RG/Passaporte
```
UsuÃ¡rio: [envia scaneamento de RG]
         â†“
Bot extrai texto (futuro: OCR com Tesseract)
         â†“
Bot: "Recebi seu documento. Preciso confirmar: 
     Seu CPF estÃ¡ registrado como XXXXX?"
```

### Caso 3: Planilha de OrÃ§amento
```
UsuÃ¡rio: [envia XLSX com preÃ§os]
         â†“
Bot extrai dados CSV
         â†“
Bot: "Vi sua planilha com 15 imÃ³veis listados.
     Posso ajudÃ¡-lo a filtrar por bairro ou valor?"
```

### Caso 4: PDF de Contrato
```
UsuÃ¡rio: [envia contrato de venda]
         â†“
Bot extrai texto completo
         â†“
Bot: "Li seu contrato. Identifiquei:
     - Valor: R$ 650.000
     - LocalizaÃ§Ã£o: Morumbi, SP
     - Forma pagamento: 50% entrada
     
     Tem dÃºvidas sobre alguma clÃ¡usula?"
```

---

## ðŸ› Troubleshooting

### Problema: "Imagem nÃ£o consegue ser analisada"
**Causa**: OpenAI API key nÃ£o configurada
**SoluÃ§Ã£o**: Adicionar `OPENAI_KEY=YOUR_OPENAI_KEY` em `.env`

### Problema: "PDF recebido mas sem texto extraÃ­vel"
**Causa**: Biblioteca `spatie/pdf-to-text` nÃ£o instalada
**SoluÃ§Ã£o**: `composer require spatie/pdf-to-text`

### Problema: Arquivo muito grande / timeout
**Causa**: Arquivo excede 50MB ou downloads lentÃ­ssimos
**SoluÃ§Ã£o**: 
- Reduzir tamanho do arquivo
- Aumentar timeout em `Http::timeout(30)` â†’ `Http::timeout(60)`

### Problema: Arquivos acumulando no disco
**Causa**: NÃ£o hÃ¡ limpeza automÃ¡tica
**SoluÃ§Ã£o**: Agendar comando: `php artisan media:cleanup --days=30` via cron

```php
// app/Console/Kernel.php
$schedule->command('media:cleanup --days=30')->daily();
```

---

## ðŸ“ˆ Performance & OtimizaÃ§Ãµes

### RecomendaÃ§Ãµes

1. **Cache de AnÃ¡lises**
   ```php
   $cacheKey = 'media_analysis_' . md5($fileContent);
   Cache::remember($cacheKey, 24*60, function() {
       return $mediaProcessor->processar($msgData);
   });
   ```

2. **Fila AssÃ­ncrona**
   - Arquivos grandes: processar em background job
   - Usar `Queue::connection('sync')` para testes

3. **Monitoramento**
   ```php
   Log::info('Media processed', [
       'tipo' => $tipoMidia,
       'tamanho_bytes' => $fileSize,
       'tempo_processamento_ms' => $timeMs,
       'cliente' => $clienteId
   ]);
   ```

---

## ðŸ”® Roadmap Futuro

- [ ] **TranscriÃ§Ã£o de Ãudio**: Integrar Whisper API
- [ ] **OCR de Imagens**: Tesseract para texto em imagens
- [ ] **AnÃ¡lise de Documentos**: Claude para summarizaÃ§Ã£o
- [ ] **Cache DistribuÃ­do**: Redis para anÃ¡lises
- [ ] **Webhook para Processamento**: Notificar quando pronto
- [ ] **Streaming de Respostas**: Para arquivos grandes
- [ ] **Suporte a VÃ­deos**: FFmpeg para extraÃ§Ã£o de frames

---

## ðŸ“ž Contato & Suporte

Para dÃºvidas ou problemas, consulte:
- Logs: `storage/logs/laravel.log`
- Arquivos processados: `storage/app/public/whatsapp_media/`
- CÃ³digo: `app/Services/MediaProcessor.php`

---

**VersÃ£o**: 1.0.0
**Ãšltima atualizaÃ§Ã£o**: 16/01/2025
**Autor**: Agente Copilot


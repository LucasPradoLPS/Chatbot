# 🤖 Agente de Processamento de Mídia - Documentação Completa

## Visão Geral

O **MediaProcessor** é um agente PHP inteligente que processa imagens, PDFs, documentos e áudio enviados via WhatsApp. Integra-se com OpenAI Vision para análise de conteúdo visual e extrai texto de documentos automaticamente.

## 📋 Funcionalidades

### ✅ Tipos de Arquivo Suportados

#### **Imagens**
- JPEG, PNG, GIF, WebP
- **Processamento**: Análise com OpenAI Vision API
- **Output**: Descrição detalhada do conteúdo visual
- **Exemplo**: Usuário envia foto de imóvel → Bot descreve características visuais

#### **PDFs**
- Formato: application/pdf
- **Processamento**: Extração de texto com pdftotext (spatie/pdf-to-text)
- **Output**: Texto extraído ou resumo do conteúdo
- **Exemplo**: Usuário envia contrato → Bot extrai e analisa termos

#### **Documentos**
- DOCX (Word), XLSX (Excel), CSV, TXT
- **Processamento**: Extração de texto baseado no tipo
- **Output**: Conteúdo estruturado e interpretável
- **Exemplo**: Usuário envia planilha de orçamento → Bot analisa números

#### **Áudio**
- OGG, MP3
- **Processamento**: Armazenamento e interface para Whisper API (futuro)
- **Output**: Informação de recebimento
- **Exemplo**: Usuário envia mensagem de voz com contexto longo

#### **Vídeo**
- Status: Não suportado (requer ffmpeg/conversão)
- **Fallback**: Mensagem pedindo ao usuário descrever em texto

---

## 🏗️ Arquitetura

### Fluxo de Processamento

```
WhatsApp (Evolution API)
         ↓
    [Webhook recebe]
         ↓
ProcessWhatsappMessage.php
         ↓
    [Detecta tipo de mídia]
         ↓
    processarMedia() method
         ↓
MediaProcessor::processar()
         ↓
  [Valida tipo/tamanho]
         ↓
  [Download do arquivo]
         ↓
[Processamento específico]
  ├─ Imagem → OpenAI Vision
  ├─ PDF → Extração de texto
  ├─ Documento → Parse XML/CSV
  └─ Áudio → Armazenamento
         ↓
[Armazenamento local]
         ↓
[Resposta contextualizada]
         ↓
Evolution API (WhatsApp)
```

### Componentes Principais

#### 1. **MediaProcessor** (`app/Services/MediaProcessor.php`)
- Serviço central de processamento
- Métodos públicos:
  - `processar(array $msgData): array` - Processa qualquer tipo de mídia
  - `limparArquivosAntigos(int $diasRetencao): array` - Remove arquivos antigos

#### 2. **ProcessWhatsappMessage** (integração)
- Importa: `use App\Services\MediaProcessor;`
- Chama: `processarMedia($tipoMensagem, $msgData, ...)`
- Métodos adicionados:
  - `processarMedia()` - Orquestra processamento
  - `montarRespostaMedia()` - Cria resposta contextualizada

#### 3. **Comandos Artisan**
- `php artisan media:process {file} --type=auto` - Processa arquivo local
- `php artisan media:cleanup --days=30` - Remove arquivos antigos

---

## 💻 Como Usar

### 1. **Processamento Automático (via WhatsApp)**

Usuário envia uma imagem para o bot:

```
Usuário: [envia imagem de imóvel]
Bot: ✅ Imagem analisada com sucesso!

Aqui está o que identifiquei:

📷 **Análise de Imagem:**
A imagem mostra uma sala de estar moderna com...
- Movedores de sofá cinza em estilo minimalista
- Piso em madeira clara
- Iluminação natural por janelas amplas
- Decoração contemporânea

Como posso ajudá-lo com relação a isso? 🤔
```

### 2. **PDF com Contrato**

```
Usuário: [envia PDF de contrato]
Bot: ✅ PDF processado com sucesso!

**Conteúdo extraído:**

CONTRATO DE COMPRA E VENDA
Partes: Vendedor: João Silva
        Comprador: Maria Santos
        
Objeto: Imóvel localizado em São Paulo, SP
        Bairro: Morumbi
        
Valor: R$ 650.000,00
Forma de pagamento: 50% na assinatura...

...(conteúdo truncado)

Como posso ajudar com este documento? 📄
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

## 🔧 Configuração

### Pré-requisitos

1. **OpenAI API Key** (em `.env`):
```env
OPENAI_KEY=sk-proj-xxxxxxxx
```

2. **Armazenamento de Arquivos**:
- Disco `public` deve estar configurado (padrão Laravel)
- Pasta `storage/app/public/whatsapp_media` será criada automaticamente

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

Ajustar em `MediaProcessor.php` conforme necessário.

---

## 📝 Exemplo de Implementação

### Estrutura de Resposta

Quando processamento **bem-sucedido**:

```php
[
    'success' => true,
    'tipo_midia' => 'image',  // image|pdf|document|audio
    'conteudo_extraido' => 'Descrição detalhada do arquivo...',
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
    'erro' => 'URL da imagem não fornecida'
]
```

### Respostas Customizadas

Editar método `montarRespostaMedia()` em `ProcessWhatsappMessage.php`:

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

## 🚀 Recursos Avançados

### 1. **OpenAI Vision para Imagens**

O agente usa `gpt-4o-mini` para análise visual:

```php
private function analisarImagemComOpenAI(string $imageUrl): string
{
    // Envia imagem para GPT-4 Vision
    // Retorna análise estruturada do conteúdo
}
```

**Customizar prompt**: Editar em `MediaProcessor.php` linha ~270

### 2. **Extração de Texto de PDFs**

Usa biblioteca Spatie:

```php
private function extrairTextoPDF(string $conteudoPDF): string
{
    $texto = (new \Spatie\PdfToText\Pdf($tempFile))->text();
    return trim($texto);
}
```

Se não instalado, retorna informação genérica.

### 3. **Processamento de Documentos DOCX**

Extrai XML interno do arquivo ZIP:

```php
if ($mimetype === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
    // Remove tags XML, preserva apenas texto
    $texto = preg_replace('/<[^>]*>/', ' ', $xmlContent);
}
```

### 4. **Integração com Estado do Thread**

Armazena metadados no histórico:

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

## 🛡️ Validações & Segurança

### Validações Implementadas

1. **Tipo de Arquivo**
   - Whitelist de MIME types suportados
   - Rejeita tipos não reconhecidos

2. **Tamanho do Arquivo**
   - Máximo 50MB (configurável)
   - Previne consumo excessivo de recursos

3. **Tipos de MIME**
   - Verifica antes de processar
   - Diferencia entre imagem, PDF, documento, áudio

4. **Tratamento de Erros**
   - Try-catch em múltiplos níveis
   - Logs detalhados de falhas
   - Respostas amigáveis ao usuário

### Recomendações de Segurança

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
├── images/        # Imagens processadas
├── documents/     # PDFs e documentos
├── audio/         # Arquivos de áudio
└── [uuid_arquivo] # Nomes únicos (evita colisão)
```

---

## 📊 Exemplos de Casos de Uso

### Caso 1: Análise de Imóvel via Foto
```
Usuário: [envia foto de apartamento]
         ↓
Bot analisa com Vision API
         ↓
Bot: "Vejo uma sala ampla com 3 janelas, piso em porcelanato,
     paredes brancas e iluminação natural excelente. 
     Gostaria de saber mais sobre localização ou preço?"
```

### Caso 2: Documento de RG/Passaporte
```
Usuário: [envia scaneamento de RG]
         ↓
Bot extrai texto (futuro: OCR com Tesseract)
         ↓
Bot: "Recebi seu documento. Preciso confirmar: 
     Seu CPF está registrado como XXXXX?"
```

### Caso 3: Planilha de Orçamento
```
Usuário: [envia XLSX com preços]
         ↓
Bot extrai dados CSV
         ↓
Bot: "Vi sua planilha com 15 imóveis listados.
     Posso ajudá-lo a filtrar por bairro ou valor?"
```

### Caso 4: PDF de Contrato
```
Usuário: [envia contrato de venda]
         ↓
Bot extrai texto completo
         ↓
Bot: "Li seu contrato. Identifiquei:
     - Valor: R$ 650.000
     - Localização: Morumbi, SP
     - Forma pagamento: 50% entrada
     
     Tem dúvidas sobre alguma cláusula?"
```

---

## 🐛 Troubleshooting

### Problema: "Imagem não consegue ser analisada"
**Causa**: OpenAI API key não configurada
**Solução**: Adicionar `OPENAI_KEY=sk-...` em `.env`

### Problema: "PDF recebido mas sem texto extraível"
**Causa**: Biblioteca `spatie/pdf-to-text` não instalada
**Solução**: `composer require spatie/pdf-to-text`

### Problema: Arquivo muito grande / timeout
**Causa**: Arquivo excede 50MB ou downloads lentíssimos
**Solução**: 
- Reduzir tamanho do arquivo
- Aumentar timeout em `Http::timeout(30)` → `Http::timeout(60)`

### Problema: Arquivos acumulando no disco
**Causa**: Não há limpeza automática
**Solução**: Agendar comando: `php artisan media:cleanup --days=30` via cron

```php
// app/Console/Kernel.php
$schedule->command('media:cleanup --days=30')->daily();
```

---

## 📈 Performance & Otimizações

### Recomendações

1. **Cache de Análises**
   ```php
   $cacheKey = 'media_analysis_' . md5($fileContent);
   Cache::remember($cacheKey, 24*60, function() {
       return $mediaProcessor->processar($msgData);
   });
   ```

2. **Fila Assíncrona**
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

## 🔮 Roadmap Futuro

- [ ] **Transcrição de Áudio**: Integrar Whisper API
- [ ] **OCR de Imagens**: Tesseract para texto em imagens
- [ ] **Análise de Documentos**: Claude para summarização
- [ ] **Cache Distribuído**: Redis para análises
- [ ] **Webhook para Processamento**: Notificar quando pronto
- [ ] **Streaming de Respostas**: Para arquivos grandes
- [ ] **Suporte a Vídeos**: FFmpeg para extração de frames

---

## 📞 Contato & Suporte

Para dúvidas ou problemas, consulte:
- Logs: `storage/logs/laravel.log`
- Arquivos processados: `storage/app/public/whatsapp_media/`
- Código: `app/Services/MediaProcessor.php`

---

**Versão**: 1.0.0
**Última atualização**: 16/01/2025
**Autor**: Agente Copilot

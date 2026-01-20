# Testes de Leitura de Mídia (Imagens e Documentos)

## Resumo dos Testes

Testado em: 13/01/2026

### ✅ DOCX
- **Status**: Funcional
- **Método**: ZipArchive (nativo PHP)
- **Log**: `[MEDIA] Texto extraído de DOCX`
- **Resultado**: Extraiu ~2200 palavras de documento de amostra (sample3.docx)
- **Observação**: Truncado em ~8000 chars e enviado à IA

### ⚠️ PDF
- **Status**: Requer `pdftotext` instalado
- **Método**: Shell command (`pdftotext -layout`)
- **Log (sem tool)**: `[MEDIA] pdftotext não encontrado no PATH.`
- **Log (com tool)**: `[MEDIA] Texto extraído do PDF com pdftotext`
- **Instrução de instalação**: Ver [SETUP.md](SETUP.md) — baixar Poppler e adicionar ao PATH
- **Fallback**: Quando pdftotext ausente, o bot bloqueia e envia mensagem pedindo conteúdo em texto

### 🖼️ Imagem
- **Status**: Funcional (upload para OpenAI)
- **Método**: Download local → upload OpenAI Files API (purpose=vision) → anexa como `image_file` no thread
- **Log**: `[MEDIA] Imagem anexada à OpenAI {'file_id': 'file-xyz...'}`
- **Observação**: Requer modelo multimodal (gpt-4o, gpt-4-turbo, etc.) no Assistant

### 📄 TXT/CSV
- **Status**: Funcional (leitura direta)
- **Método**: `file_get_contents` com sanitização de controles
- **Log**: `[MEDIA] Texto extraído de TXT/CSV`
- **Observação**: Truncado em ~8000 chars

### ❌ DOC (Word antigo)
- **Status**: Opcional (`antiword` se disponível)
- **Método**: Shell command (`antiword`)
- **Log**: `[MEDIA] Texto extraído de DOC com antiword` ou `[MEDIA] antiword não disponível...`
- **Fallback**: Bot pede conteúdo em texto se tool ausente

## Como Testar Localmente

1. Certifique-se de ter uma instância no banco:
   ```bash
   php artisan db:seed
   ```

2. Teste diretamente (sem HTTP/queue):
   ```bash
   php test_media_direct.php docx
   php test_media_direct.php pdf
   php test_media_direct.php txt
   php test_media_direct.php csv
   php test_media_direct.php image
   ```

3. Verifique logs:
   ```bash
   Get-Content storage\logs\laravel.log -Tail 50
   ```

## Próximas Melhorias

- [ ] Suporte a `.xlsx` (PhpSpreadsheet ou similar)
- [ ] Transcrição de áudio (Whisper API)
- [ ] OCR nativo para imagens (Tesseract) além de visão da IA
- [ ] Limite configurável de tamanho de documento

## Scripts de Teste

- `test_media_direct.php`: Processa mídia via job direto (sem HTTP)
- `test_media.php`: Envia webhook HTTP ao endpoint local (requer server rodando)

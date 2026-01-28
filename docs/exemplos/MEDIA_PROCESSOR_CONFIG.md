# âš™ï¸ ConfiguraÃ§Ã£o AvanÃ§ada do MediaProcessor

## VariÃ¡veis de Ambiente

Adicione ao seu arquivo `.env`:

```env
# OpenAI Configuration
OPENAI_KEY=YOUR_OPENAI_KEY
OPENAI_MODEL=gpt-4o-mini

# Media Processor Settings
MEDIA_MAX_FILE_SIZE=52428800              # 50MB em bytes
MEDIA_RETENTION_DAYS=30                   # Deletar arquivos > X dias
MEDIA_STORAGE_DISK=public                 # Disco de armazenamento
MEDIA_DOWNLOAD_TIMEOUT=30                 # Timeout em segundos

# Processamento de PDFs
PDF_EXTRACTION_ENABLED=true
PDF_OCR_ENABLED=false                     # Futuro: Tesseract

# Processamento de Ãudio
AUDIO_TRANSCRIPTION_ENABLED=false         # Futuro: Whisper API
AUDIO_MAX_DURATION=300                    # 5 minutos

# Logging
LOG_LEVEL=debug
LOG_CHANNEL=stack
```

## CustomizaÃ§Ã£o do MediaProcessor

### 1. Aumentar Limite de Tamanho

```php
// app/Services/MediaProcessor.php
private $maxFileSize = 100 * 1024 * 1024; // 100MB

// Ou via .env
private $maxFileSize = config('media.max_file_size', 50 * 1024 * 1024);
```

### 2. Customizar Prompt do OpenAI Vision

```php
// app/Services/MediaProcessor.php, linha ~270
private function analisarImagemComOpenAI(string $imageUrl): string
{
    $response = Http::withToken($this->openaiKey)
        ->timeout(30)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'SEU PROMPT CUSTOMIZADO AQUI' // â† EDITE AQUI
                        ],
                        // ... resto do cÃ³digo
```

**Exemplos de prompts customizados:**

```php
// Para anÃ¡lise de imÃ³vel
'text' => 'VocÃª Ã© especialista em imÃ³veis. Analise esta foto e identifique: '
         . 'condiÃ§Ã£o geral, nÃºmero aproximado de cÃ´modos, acabamento, '
         . 'caracterÃ­sticas positivas e negativas. Seja tÃ©cnico mas conciso.',

// Para anÃ¡lise de documentos
'text' => 'VocÃª Ã© advogado especialista. Analise este documento e identifique: '
         . 'partes envolvidas, termos principais, valores, prazos, '
         . 'riscos legais. Destaque os 5 pontos mais importantes.',

// Para anÃ¡lise de plantas
'text' => 'VocÃª Ã© arquiteto. Analisando esta planta: '
         . 'identifique metragem aproximada, layout, fluxos, problemas. '
         . 'Descreva em termos tÃ©cnicos.',
```

### 3. Customizar Formatos Suportados

```php
// app/Services/MediaProcessor.php
const SUPPORTED_IMAGE_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/tiff',  // â† ADICIONE
];

const SUPPORTED_PDF_TYPES = [
    'application/pdf',
];

const SUPPORTED_DOC_TYPES = [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv',
    'application/json',  // â† ADICIONE
];
```

### 4. Customizar Armazenamento

```php
// app/Services/MediaProcessor.php
private $mediaDisk = 's3';         // Usar S3 ao invÃ©s de local
private $mediaPath = 'whatsapp_media/2025-01'; // Organizar por mÃªs

// Ou via config
$this->mediaDisk = config('filesystems.default');
```

### 5. Adicionar Cache para AnÃ¡lises

```php
// app/Services/MediaProcessor.php
use Illuminate\Support\Facades\Cache;

private function analisarImagemComOpenAI(string $imageUrl): string
{
    $cacheKey = 'image_analysis_' . md5($imageUrl);
    
    // Retornar cache se existir (24 horas)
    return Cache::remember($cacheKey, 24*60, function() use ($imageUrl) {
        // ... fazer anÃ¡lise com OpenAI
        $descricao = $response['choices'][0]['message']['content'];
        return $descricao;
    });
}
```

### 6. Adicionar Webhook de NotificaÃ§Ã£o

```php
// app/Services/MediaProcessor.php
private function notificarProcessamento(string $clienteId, array $resultado)
{
    if ($resultado['success']) {
        Http::post(config('media.webhook_url'), [
            'evento' => 'media_processada',
            'cliente' => $clienteId,
            'tipo' => $resultado['tipo_midia'],
            'arquivo' => $resultado['arquivo_local'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### 7. IntegraÃ§Ã£o com Slack/Discord

```php
// app/Services/MediaProcessor.php
private function notificarSlack(string $tipoMidia, string $conteudo)
{
    if (!config('media.notify_slack')) {
        return;
    }
    
    Http::post(env('SLACK_WEBHOOK_URL'), [
        'text' => "ðŸ“„ Arquivo processado: $tipoMidia",
        'blocks' => [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "```\n" . substr($conteudo, 0, 200) . "\n```"
                ]
            ]
        ]
    ]);
}
```

## ConfiguraÃ§Ã£o com Scheduler

### Limpeza AutomÃ¡tica de Arquivos

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Limpar arquivos com mais de 30 dias diariamente
    $schedule->command('media:cleanup --days=30')
        ->daily()
        ->at('02:00')  // 2 AM
        ->withoutOverlapping();
    
    // Limpeza mais agressiva aos domingos
    $schedule->command('media:cleanup --days=7')
        ->weekly()
        ->sundays()
        ->at('03:00');
}
```

## ConfiguraÃ§Ã£o com Queues

Para processar arquivos grandes em background:

```php
// app/Jobs/ProcessMediaJob.php (novo arquivo)
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\MediaProcessor;

class ProcessMediaJob implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private array $msgData,
        private string $instance,
        private string $remetente
    ) {}
    
    public function handle()
    {
        $mediaProcessor = new MediaProcessor();
        $resultado = $mediaProcessor->processar($this->msgData);
        
        // Enviar resposta...
    }
}
```

Modificar `ProcessWhatsappMessage.php`:

```php
// Dispatch para queue ao invÃ©s de processar inline
ProcessMediaJob::dispatch($msgData, $instance, $remetente)
    ->onConnection('redis')
    ->onQueue('media');
```

## ConfiguraÃ§Ã£o com Redis Cache

```php
// app/Services/MediaProcessor.php
use Illuminate\Support\Facades\Redis;

private function analisarImagemComOpenAI(string $imageUrl): string
{
    $cacheKey = 'image:' . md5($imageUrl);
    
    // Tentar cache Redis
    $cached = Redis::get($cacheKey);
    if ($cached) {
        return $cached;
    }
    
    // Fazer anÃ¡lise...
    $resultado = $response['choices'][0]['message']['content'];
    
    // Armazenar em cache por 24 horas
    Redis::setex($cacheKey, 24*60*60, $resultado);
    
    return $resultado;
}
```

## ConfiguraÃ§Ã£o com S3 Storage

```php
// .env
AWS_ACCESS_KEY_ID=xxxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=meu-bucket-midia

// app/Services/MediaProcessor.php
private $mediaDisk = 's3';
private $mediaPath = 'whatsapp_media';
```

## Monitoramento & Alertas

```php
// app/Services/MediaProcessor.php
private function processarImagem(array $imageData): array
{
    $startTime = microtime(true);
    
    try {
        // ... processamento
        
        $duration = microtime(true) - $startTime;
        
        // Alertar se muito lento
        if ($duration > 10) {
            Log::warning('Processamento lento de imagem', [
                'duracao_segundos' => $duration,
                'url' => $imageData['url'],
                'tamanho' => $fileSize
            ]);
            
            // Notificar admin
            Notification::send(
                User::where('role', 'admin')->get(),
                new SlowMediaProcessingNotification($duration)
            );
        }
        
        return ['success' => true, ...];
        
    } catch (Exception $e) {
        Log::error('Erro ao processar imagem', [
            'erro' => $e->getMessage(),
            'duracao' => microtime(true) - $startTime
        ]);
        
        // Alertar sobre erro
        throw $e;
    }
}
```

## Observabilidade com Datadog/New Relic

```php
// app/Services/MediaProcessor.php
private function processar(array $msgData): array
{
    // IntegraÃ§Ã£o Datadog
    \DDTrace\trace_function('MediaProcessor::processar', function() use ($msgData) {
        // ... processamento
    });
    
    // Ou integraÃ§Ã£o New Relic
    newrelic_add_custom_metric('media.processed', 1);
    newrelic_add_custom_parameter('media_type', $tipoMidia);
}
```

## Teste de Carga

```bash
#!/bin/bash
# load_test.sh - Testa processamento com mÃºltiplas requisiÃ§Ãµes

for i in {1..10}; do
    echo "RequisiÃ§Ã£o $i"
    php test_media_webhook.php image &
done

wait
echo "Testes concluÃ­dos"
```

## DocumentaÃ§Ã£o das VariÃ¡veis de Ambiente

```env
# ==========================================
# OPENAI CONFIGURATION
# ==========================================
OPENAI_KEY=YOUR_OPENAI_KEY                    # Chave da API OpenAI
OPENAI_MODEL=gpt-4o-mini                  # Modelo a usar (gpt-4o-mini, gpt-4)

# ==========================================
# MEDIA PROCESSOR CONFIGURATION
# ==========================================
MEDIA_MAX_FILE_SIZE=52428800              # MÃ¡ximo arquivo (bytes)
MEDIA_RETENTION_DAYS=30                   # Dias antes de deletar
MEDIA_STORAGE_DISK=public                 # Disco (public, s3, etc)
MEDIA_DOWNLOAD_TIMEOUT=30                 # Timeout download (segundos)

# ==========================================
# PDF CONFIGURATION
# ==========================================
PDF_EXTRACTION_ENABLED=true               # Habilitar extraÃ§Ã£o
PDF_LANGUAGE=por                          # Idioma (por, eng, spa)

# ==========================================
# AUDIO CONFIGURATION
# ==========================================
AUDIO_TRANSCRIPTION_ENABLED=false         # Futuro: Whisper
AUDIO_MAX_DURATION=300                    # Max segundos

# ==========================================
# LOGGING & MONITORING
# ==========================================
LOG_LEVEL=debug                           # debug|info|warning|error
MEDIA_WEBHOOK_URL=https://...             # Para notificaÃ§Ãµes
SLACK_WEBHOOK_URL=https://hooks.slack.com # Para alertas
```

---

**PrÃ³ximas atualizaÃ§Ãµes**: IntegraÃ§Ã£o com Whisper, OCR, S3, Redis


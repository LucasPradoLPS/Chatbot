# ⚙️ Configuração Avançada do MediaProcessor

## Variáveis de Ambiente

Adicione ao seu arquivo `.env`:

```env
# OpenAI Configuration
OPENAI_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4o-mini

# Media Processor Settings
MEDIA_MAX_FILE_SIZE=52428800              # 50MB em bytes
MEDIA_RETENTION_DAYS=30                   # Deletar arquivos > X dias
MEDIA_STORAGE_DISK=public                 # Disco de armazenamento
MEDIA_DOWNLOAD_TIMEOUT=30                 # Timeout em segundos

# Processamento de PDFs
PDF_EXTRACTION_ENABLED=true
PDF_OCR_ENABLED=false                     # Futuro: Tesseract

# Processamento de Áudio
AUDIO_TRANSCRIPTION_ENABLED=false         # Futuro: Whisper API
AUDIO_MAX_DURATION=300                    # 5 minutos

# Logging
LOG_LEVEL=debug
LOG_CHANNEL=stack
```

## Customização do MediaProcessor

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
                            'text' => 'SEU PROMPT CUSTOMIZADO AQUI' // ← EDITE AQUI
                        ],
                        // ... resto do código
```

**Exemplos de prompts customizados:**

```php
// Para análise de imóvel
'text' => 'Você é especialista em imóveis. Analise esta foto e identifique: '
         . 'condição geral, número aproximado de cômodos, acabamento, '
         . 'características positivas e negativas. Seja técnico mas conciso.',

// Para análise de documentos
'text' => 'Você é advogado especialista. Analise este documento e identifique: '
         . 'partes envolvidas, termos principais, valores, prazos, '
         . 'riscos legais. Destaque os 5 pontos mais importantes.',

// Para análise de plantas
'text' => 'Você é arquiteto. Analisando esta planta: '
         . 'identifique metragem aproximada, layout, fluxos, problemas. '
         . 'Descreva em termos técnicos.',
```

### 3. Customizar Formatos Suportados

```php
// app/Services/MediaProcessor.php
const SUPPORTED_IMAGE_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/tiff',  // ← ADICIONE
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
    'application/json',  // ← ADICIONE
];
```

### 4. Customizar Armazenamento

```php
// app/Services/MediaProcessor.php
private $mediaDisk = 's3';         // Usar S3 ao invés de local
private $mediaPath = 'whatsapp_media/2025-01'; // Organizar por mês

// Ou via config
$this->mediaDisk = config('filesystems.default');
```

### 5. Adicionar Cache para Análises

```php
// app/Services/MediaProcessor.php
use Illuminate\Support\Facades\Cache;

private function analisarImagemComOpenAI(string $imageUrl): string
{
    $cacheKey = 'image_analysis_' . md5($imageUrl);
    
    // Retornar cache se existir (24 horas)
    return Cache::remember($cacheKey, 24*60, function() use ($imageUrl) {
        // ... fazer análise com OpenAI
        $descricao = $response['choices'][0]['message']['content'];
        return $descricao;
    });
}
```

### 6. Adicionar Webhook de Notificação

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

### 7. Integração com Slack/Discord

```php
// app/Services/MediaProcessor.php
private function notificarSlack(string $tipoMidia, string $conteudo)
{
    if (!config('media.notify_slack')) {
        return;
    }
    
    Http::post(env('SLACK_WEBHOOK_URL'), [
        'text' => "📄 Arquivo processado: $tipoMidia",
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

## Configuração com Scheduler

### Limpeza Automática de Arquivos

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

## Configuração com Queues

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
// Dispatch para queue ao invés de processar inline
ProcessMediaJob::dispatch($msgData, $instance, $remetente)
    ->onConnection('redis')
    ->onQueue('media');
```

## Configuração com Redis Cache

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
    
    // Fazer análise...
    $resultado = $response['choices'][0]['message']['content'];
    
    // Armazenar em cache por 24 horas
    Redis::setex($cacheKey, 24*60*60, $resultado);
    
    return $resultado;
}
```

## Configuração com S3 Storage

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
    // Integração Datadog
    \DDTrace\trace_function('MediaProcessor::processar', function() use ($msgData) {
        // ... processamento
    });
    
    // Ou integração New Relic
    newrelic_add_custom_metric('media.processed', 1);
    newrelic_add_custom_parameter('media_type', $tipoMidia);
}
```

## Teste de Carga

```bash
#!/bin/bash
# load_test.sh - Testa processamento com múltiplas requisições

for i in {1..10}; do
    echo "Requisição $i"
    php test_media_webhook.php image &
done

wait
echo "Testes concluídos"
```

## Documentação das Variáveis de Ambiente

```env
# ==========================================
# OPENAI CONFIGURATION
# ==========================================
OPENAI_KEY=sk-proj-...                    # Chave da API OpenAI
OPENAI_MODEL=gpt-4o-mini                  # Modelo a usar (gpt-4o-mini, gpt-4)

# ==========================================
# MEDIA PROCESSOR CONFIGURATION
# ==========================================
MEDIA_MAX_FILE_SIZE=52428800              # Máximo arquivo (bytes)
MEDIA_RETENTION_DAYS=30                   # Dias antes de deletar
MEDIA_STORAGE_DISK=public                 # Disco (public, s3, etc)
MEDIA_DOWNLOAD_TIMEOUT=30                 # Timeout download (segundos)

# ==========================================
# PDF CONFIGURATION
# ==========================================
PDF_EXTRACTION_ENABLED=true               # Habilitar extração
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
MEDIA_WEBHOOK_URL=https://...             # Para notificações
SLACK_WEBHOOK_URL=https://hooks.slack.com # Para alertas
```

---

**Próximas atualizações**: Integração com Whisper, OCR, S3, Redis

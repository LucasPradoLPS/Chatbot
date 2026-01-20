<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MediaProcessor;

class ProcessMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:process {file} {--type=auto}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa um arquivo de mídia (imagem, PDF, documento) e exibe o conteúdo extraído';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $type = $this->option('type');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: $filePath");
            return 1;
        }

        $this->info("📂 Processando arquivo: $filePath");

        // Lê o arquivo
        $conteudo = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);
        
        if ($type === 'auto') {
            // Detecta tipo automaticamente
            if (strpos($mimeType, 'image/') === 0) {
                $type = 'image';
            } elseif ($mimeType === 'application/pdf') {
                $type = 'pdf';
            } else {
                $type = 'document';
            }
        }

        $this->info("🔍 Tipo detectado: $type (MIME: $mimeType)");

        $mediaProcessor = new MediaProcessor();

        // Prepara dados para processar
        $msgData = match ($type) {
            'image' => [
                'imageMessage' => [
                    'url' => 'file://' . realpath($filePath),
                    'mimetype' => $mimeType,
                    'caption' => 'Análise de imagem'
                ]
            ],
            'pdf' => [
                'documentMessage' => [
                    'url' => 'file://' . realpath($filePath),
                    'mimetype' => 'application/pdf',
                    'filename' => basename($filePath)
                ]
            ],
            default => [
                'documentMessage' => [
                    'url' => 'file://' . realpath($filePath),
                    'mimetype' => $mimeType,
                    'filename' => basename($filePath)
                ]
            ]
        };

        // Nota: Para arquivos locais, o MediaProcessor não conseguirá fazer download via HTTP
        // Este comando é mais para demostração. Em produção, deve usar URLs válidas.
        $this->warn("⚠️  Para processar corretamente, o arquivo precisa estar em uma URL acessível.");
        $this->line("   Este comando é melhor usado com URLs remotas ou para testar a lógica.");

        return 0;
    }
}

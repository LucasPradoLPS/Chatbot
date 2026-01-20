<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MediaProcessor;

class CleanupMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove arquivos de mídia armazenados há mais de X dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = $this->option('days');

        $this->info("🗑️  Iniciando limpeza de arquivos de mídia com mais de $dias dias...");

        $resultado = MediaProcessor::limparArquivosAntigos($dias);

        if ($resultado['erro']) {
            $this->error("❌ Erro ao limpar: " . $resultado['erro']);
            return 1;
        }

        $this->info("✅ Limpeza concluída!");
        $this->line("📊 Arquivos removidos: " . $resultado['removidos']);

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug - Listar e verificar arquivos de log';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logsDir = storage_path('logs');
        
        $this->info('=== DEBUG - INFORMAÇÕES DE LOGS ===');
        $this->info("Caminho: $logsDir");
        $this->info('Existe: ' . (is_dir($logsDir) ? 'SIM' : 'NÃO'));
        $this->info('Legível: ' . (is_readable($logsDir) ? 'SIM' : 'NÃO'));
        $this->info('Permissões: ' . substr(sprintf('%o', fileperms($logsDir)), -4));
        $this->line('');

        if (!is_dir($logsDir)) {
            $this->error('❌ Diretório de logs não existe!');
            Log::error('Diretório de logs não existe', ['path' => $logsDir]);
            return 1;
        }

        if (!is_readable($logsDir)) {
            $this->error('❌ Diretório de logs não é legível!');
            Log::error('Diretório de logs não é legível', ['path' => $logsDir]);
            return 1;
        }

        $files = scandir($logsDir);
        $logFiles = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $logsDir . DIRECTORY_SEPARATOR . $file;
            
            if (is_file($filePath)) {
                $size = filesize($filePath);
                $readable = is_readable($filePath);
                $lastModified = date('Y-m-d H:i:s', filemtime($filePath));
                
                $this->info("📄 $file");
                $this->line("   Tamanho: " . $this->formatBytes($size) . " ($size bytes)");
                $this->line("   Legível: " . ($readable ? 'SIM' : 'NÃO'));
                $this->line("   Modificado: $lastModified");
                
                if ($readable && $size > 0) {
                    $lines = file($filePath);
                    $this->line("   Linhas: " . count($lines));
                    $this->line("   Últimas 3 linhas:");
                    $lastLines = array_slice($lines, -3);
                    foreach ($lastLines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $this->line("      > " . mb_substr($line, 0, 120));
                        }
                    }
                } else if ($size === 0) {
                    $this->warn("   ⚠️ Arquivo vazio");
                }
                
                $logFiles[] = $file;
                $this->line('');
            }
        }

        $this->info('=== RESUMO ===');
        $this->info("Total de arquivos: " . count($logFiles));
        
        if (empty($logFiles)) {
            $this->warn('❌ Nenhum arquivo de log encontrado!');
            Log::warning('Nenhum arquivo de log encontrado');
            return 1;
        }
        
        $this->info('✓ Arquivos encontrados:');
        foreach ($logFiles as $file) {
            $this->line("   - $file");
        }

        // Log do sucesso
        Log::info('Debug logs executado com sucesso', [
            'total_files' => count($logFiles),
            'files' => $logFiles,
        ]);

        $this->info('');
        $this->info('✅ Logs listados com sucesso!');
        
        return 0;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

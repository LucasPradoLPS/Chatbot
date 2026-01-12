<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LogController extends Controller
{
    /**
     * Lista todos os logs com debug detalhado
     */
    public function index()
    {
        $logsDir = storage_path('logs');
        
        // Debug: verificar se o diretório existe
        Log::info('Debug - Listando logs', [
            'logs_dir' => $logsDir,
            'dir_exists' => is_dir($logsDir),
            'is_readable' => is_readable($logsDir),
            'dir_permissions' => substr(sprintf('%o', fileperms($logsDir)), -4),
        ]);

        $response = [
            'debug' => [
                'logs_dir' => $logsDir,
                'dir_exists' => is_dir($logsDir),
                'is_readable' => is_readable($logsDir),
                'dir_permissions' => substr(sprintf('%o', fileperms($logsDir)), -4),
                'timestamp' => now()->toISOString(),
            ],
            'files' => [],
        ];

        // Verificar se o diretório existe
        if (!is_dir($logsDir)) {
            Log::error('Diretório de logs não existe', ['path' => $logsDir]);
            $response['error'] = 'Diretório de logs não existe';
            return response()->json($response, 500);
        }

        // Tentar listar os arquivos
        try {
            $files = scandir($logsDir);
            
            Log::info('Arquivos encontrados no scandir', [
                'count' => count($files),
                'files' => $files,
            ]);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $logsDir . DIRECTORY_SEPARATOR . $file;
                
                if (is_file($filePath)) {
                    $fileInfo = [
                        'filename' => $file,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'size_readable' => $this->formatBytes(filesize($filePath)),
                        'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
                        'is_readable' => is_readable($filePath),
                        'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                    ];

                    // Tentar ler as últimas linhas do arquivo
                    try {
                        $content = file_get_contents($filePath);
                        if ($content) {
                            $lines = array_filter(explode("\n", $content));
                            $fileInfo['total_lines'] = count($lines);
                            // Últimas 10 linhas
                            $fileInfo['last_lines'] = array_slice($lines, -10);
                        } else {
                            $fileInfo['content'] = 'Arquivo vazio';
                        }
                    } catch (\Exception $e) {
                        $fileInfo['error_reading'] = $e->getMessage();
                    }

                    $response['files'][] = $fileInfo;
                    
                    Log::info('Arquivo processado', [
                        'filename' => $file,
                        'size' => filesize($filePath),
                    ]);
                }
            }

            Log::info('Debug - Logs listados com sucesso', [
                'total_files' => count($response['files']),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $response['error'] = $e->getMessage();
            return response()->json($response, 500);
        }

        return response()->json($response);
    }

    /**
     * Retorna o conteúdo completo de um arquivo de log específico
     */
    public function show($filename)
    {
        $logsDir = storage_path('logs');
        $filePath = $logsDir . DIRECTORY_SEPARATOR . $filename;

        Log::info('Debug - Acessando arquivo de log', [
            'filename' => $filename,
            'full_path' => $filePath,
            'file_exists' => file_exists($filePath),
            'is_readable' => is_readable($filePath),
        ]);

        // Validar que o arquivo está dentro do diretório de logs
        if (realpath($filePath) === false || strpos(realpath($filePath), realpath($logsDir)) !== 0) {
            Log::warning('Tentativa de acesso a arquivo fora do diretório de logs', [
                'requested' => $filePath,
            ]);
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        if (!file_exists($filePath)) {
            Log::warning('Arquivo de log não encontrado', [
                'path' => $filePath,
            ]);
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        try {
            $content = file_get_contents($filePath);
            
            // Dividir em linhas e reverter para mostrar as mais recentes primeiro
            $lines = array_filter(explode("\n", $content));
            $lines = array_reverse($lines);

            Log::info('Arquivo de log lido com sucesso', [
                'filename' => $filename,
                'total_lines' => count($lines),
            ]);

            return response()->json([
                'filename' => $filename,
                'size' => filesize($filePath),
                'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'total_lines' => count($lines),
                'lines' => $lines,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao ler arquivo de log', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Limpar um arquivo de log
     */
    public function clear($filename)
    {
        $logsDir = storage_path('logs');
        $filePath = $logsDir . DIRECTORY_SEPARATOR . $filename;

        // Validar que o arquivo está dentro do diretório de logs
        if (realpath($filePath) === false || strpos(realpath($filePath), realpath($logsDir)) !== 0) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        try {
            file_put_contents($filePath, '');
            
            Log::info('Arquivo de log limpo', [
                'filename' => $filename,
            ]);

            return response()->json(['message' => 'Arquivo limpo com sucesso']);

        } catch (\Exception $e) {
            Log::error('Erro ao limpar arquivo de log', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Formatar bytes em formato legível
     */
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

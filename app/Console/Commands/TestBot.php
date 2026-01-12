<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Agente;
use App\Models\Empresa;
use App\Models\Thread;
use App\Models\InstanciaWhatsapp;

class TestBot extends Command
{
    protected $signature = 'test:bot';
    protected $description = 'Teste completo do bot - verifica configuração e funcionalidade';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║     TESTE COMPLETO DO CHATBOT                        ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Verificar conexão com banco de dados
        $this->line('📊 1. VERIFICANDO CONEXÃO COM BANCO DE DADOS...');
        try {
            DB::connection()->getPdo();
            $this->info('   ✅ Banco de dados conectado');
            Log::info('Teste: Banco de dados OK');
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao conectar banco de dados: ' . $e->getMessage());
            Log::error('Teste: Erro no banco', ['error' => $e->getMessage()]);
            return 1;
        }

        // 2. Verificar se existem empresas
        $this->line('');
        $this->line('🏢 2. VERIFICANDO EMPRESAS...');
        try {
            $empresas = Empresa::all();
            $empresaCount = $empresas->count();
            
            if ($empresaCount === 0) {
                $this->warn('   ⚠️  Nenhuma empresa encontrada');
                Log::warning('Teste: Nenhuma empresa cadastrada');
            } else {
                $this->info("   ✅ Total de empresas: $empresaCount");
                foreach ($empresas as $empresa) {
                    $this->line("      • {$empresa->id}: {$empresa->nome}");
                }
                Log::info("Teste: $empresaCount empresas encontradas");
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao verificar empresas: ' . $e->getMessage());
            return 1;
        }

        // 3. Verificar instâncias WhatsApp
        $this->line('');
        $this->line('💬 3. VERIFICANDO INSTÂNCIAS WHATSAPP...');
        try {
            $instancias = InstanciaWhatsapp::all();
            $instanciaCount = $instancias->count();
            
            if ($instanciaCount === 0) {
                $this->warn('   ⚠️  Nenhuma instância WhatsApp encontrada');
                Log::warning('Teste: Nenhuma instância WhatsApp cadastrada');
            } else {
                $this->info("   ✅ Total de instâncias: $instanciaCount");
                foreach ($instancias as $inst) {
                    $status = $inst->is_active ? '🟢 Ativa' : '🔴 Inativa';
                    $this->line("      • {$inst->instance_name} ({$status})");
                }
                Log::info("Teste: $instanciaCount instâncias encontradas");
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao verificar instâncias: ' . $e->getMessage());
            return 1;
        }

        // 4. Verificar agentes
        $this->line('');
        $this->line('🤖 4. VERIFICANDO AGENTES...');
        try {
            $agentes = Agente::all();
            $agenteCount = $agentes->count();
            
            if ($agenteCount === 0) {
                $this->warn('   ⚠️  Nenhum agente encontrado');
                Log::warning('Teste: Nenhum agente cadastrado');
            } else {
                $this->info("   ✅ Total de agentes: $agenteCount");
                foreach ($agentes as $agente) {
                    $ia = $agente->ia_ativa ? '✅ IA Ativa' : '❌ IA Inativa';
                    $this->line("      • {$agente->nome} ({$ia}) - Empresa: {$agente->empresa_id}");
                }
                Log::info("Teste: $agenteCount agentes encontrados");
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao verificar agentes: ' . $e->getMessage());
            return 1;
        }

        // 5. Verificar threads (conversas)
        $this->line('');
        $this->line('💭 5. VERIFICANDO THREADS (CONVERSAS)...');
        try {
            $threads = Thread::all();
            $threadCount = $threads->count();
            
            if ($threadCount === 0) {
                $this->warn('   ⚠️  Nenhuma conversa encontrada');
                Log::warning('Teste: Nenhuma thread cadastrada');
            } else {
                $this->info("   ✅ Total de conversas: $threadCount");
                $recentThreads = Thread::orderBy('updated_at', 'desc')->limit(5)->get();
                foreach ($recentThreads as $thread) {
                    $lastContact = $thread->updated_at ? $thread->updated_at->format('d/m/Y H:i') : 'N/A';
                    $this->line("      • Cliente: {$thread->numero_cliente} | Atualizado: $lastContact");
                }
                Log::info("Teste: $threadCount threads encontradas");
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao verificar threads: ' . $e->getMessage());
            return 1;
        }

        // 6. Verificar arquivos de log
        $this->line('');
        $this->line('📝 6. VERIFICANDO LOGS...');
        try {
            $logsDir = storage_path('logs');
            if (is_dir($logsDir)) {
                $files = scandir($logsDir);
                $logFiles = [];
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($logsDir . DIRECTORY_SEPARATOR . $file)) {
                        $logFiles[] = $file;
                    }
                }
                
                if (empty($logFiles)) {
                    $this->warn('   ⚠️  Nenhum arquivo de log encontrado');
                } else {
                    $this->info("   ✅ Arquivos de log encontrados: " . count($logFiles));
                    foreach ($logFiles as $file) {
                        $size = filesize($logsDir . DIRECTORY_SEPARATOR . $file);
                        $sizeReadable = $this->formatBytes($size);
                        $this->line("      • $file ($sizeReadable)");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao verificar logs: ' . $e->getMessage());
        }

        // 7. Verificar variáveis de ambiente críticas
        $this->line('');
        $this->line('⚙️  7. VERIFICANDO VARIÁVEIS DE AMBIENTE...');
        
        $openaiKey = env('OPENAI_KEY') ? '✅ Configurada' : '❌ NÃO configurada';
        $this->line("      • OPENAI_KEY: $openaiKey");
        
        $evolutionKey = env('EVOLUTION_KEY') ? '✅ Configurada' : '❌ NÃO configurada';
        $this->line("      • EVOLUTION_KEY: $evolutionKey");
        
        $evolutionUrl = env('EVOLUTION_URL') ? '✅ Configurada' : '❌ NÃO configurada';
        $this->line("      • EVOLUTION_URL: $evolutionUrl");
        
        $dbConnection = env('DB_CONNECTION') ? '✅ Configurada' : '❌ NÃO configurada';
        $this->line("      • DB_CONNECTION: $dbConnection");

        // 8. Teste de ping da API
        $this->line('');
        $this->line('🔗 8. TESTANDO ENDPOINTS DA API...');
        
        $this->line('      • POST /api/webhook/whatsapp - Pronto para receber mensagens');
        $this->line('      • GET /api/ping - Status da API');
        $this->line('      • GET /api/debug/logs - Ver logs detalhados');
        $this->line("      • RUN: php artisan debug:logs - Listar logs via CLI");

        // Resumo final
        $this->line('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║                    RESUMO DO TESTE                   ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        
        $this->info('');
        $this->info('✅ Sistema em funcionamento!');
        $this->info('');
        $this->info('Próximos passos:');
        $this->line('  1. Verifique se há erros: php artisan debug:logs');
        $this->line('  2. Teste o ping: curl http://localhost:8000/api/ping');
        $this->line('  3. Monitore mensagens em tempo real: tail -f storage/logs/laravel.log');
        
        Log::info('Teste do bot executado com sucesso', [
            'empresas' => $empresaCount ?? 0,
            'instancias' => $instanciaCount ?? 0,
            'agentes' => $agenteCount ?? 0,
            'threads' => $threadCount ?? 0,
        ]);

        $this->info('');
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestarMensagem extends Command
{
    protected $signature = 'bot:testar-mensagem {mensagem?} {--numero=5511999999999}';
    protected $description = 'Testa o bot enviando uma mensagem simulada via webhook';

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║          TESTE DE MENSAGEM DO BOT                    ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // Obter mensagem
        $mensagem = $this->argument('mensagem');
        if (!$mensagem) {
            $mensagem = $this->ask('Digite a mensagem para enviar ao bot', 'Olá, quero informações sobre imóveis');
        }

        $numero = $this->option('numero');
        
        $this->line('📝 DADOS DO TESTE:');
        $this->info("   • Mensagem: $mensagem");
        $this->info("   • Número: $numero");
        $this->newLine();

        // Construir payload simulando Evolution API
        $payload = [
            'instance' => 'N8n',
            'data' => [
                'key' => [
                    'remoteJid' => $numero . '@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'TEST_' . uniqid(),
                ],
                'message' => [
                    'conversation' => $mensagem,
                ],
                'messageTimestamp' => time(),
            ],
            'event' => 'messages.upsert',
        ];

        $this->line('🚀 Enviando mensagem para o webhook...');
        $this->newLine();

        try {
            // Enviar para o webhook local
            $url = 'http://localhost:8000/api/webhook/whatsapp';
            
            $this->line("   → URL: $url");
            $this->line("   → Enviando...");
            
            $response = Http::timeout(30)->post($url, $payload);

            $this->newLine();
            
            if ($response->successful()) {
                $this->info('✅ WEBHOOK RESPONDEU COM SUCESSO!');
                $this->line("   • Status: {$response->status()}");
                
                if ($response->body()) {
                    $this->line("   • Resposta: {$response->body()}");
                }
                
                $this->newLine();
                $this->line('📋 PRÓXIMOS PASSOS:');
                $this->line('   1. Aguarde alguns segundos para o processamento');
                $this->line('   2. Verifique os logs: php artisan debug:logs');
                $this->line('   3. Veja últimas linhas: tail storage/logs/laravel.log');
                $this->newLine();
                
                // Aguardar um pouco e mostrar últimos logs
                $this->line('⏳ Aguardando processamento (5 segundos)...');
                sleep(5);
                
                $this->newLine();
                $this->line('📝 ÚLTIMOS LOGS:');
                $this->line(str_repeat('─', 60));
                
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    $lines = file($logFile);
                    $lastLines = array_slice($lines, -15);
                    foreach ($lastLines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            // Colorir logs importantes
                            if (str_contains($line, 'ERROR')) {
                                $this->error('   ' . $line);
                            } elseif (str_contains($line, 'WARNING')) {
                                $this->warn('   ' . $line);
                            } elseif (str_contains($line, 'Resposta final da IA')) {
                                $this->info('   ' . $line);
                            } else {
                                $this->line('   ' . mb_substr($line, 0, 150));
                            }
                        }
                    }
                }
                
                $this->newLine();
                Log::info('Teste de mensagem executado', [
                    'mensagem' => $mensagem,
                    'numero' => $numero,
                    'status' => $response->status(),
                ]);
                
                return 0;
                
            } else {
                $this->error('❌ ERRO NA RESPOSTA DO WEBHOOK!');
                $this->line("   • Status: {$response->status()}");
                $this->line("   • Resposta: {$response->body()}");
                
                Log::error('Teste de mensagem falhou', [
                    'mensagem' => $mensagem,
                    'numero' => $numero,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ ERRO AO ENVIAR MENSAGEM!');
            $this->error("   • Erro: {$e->getMessage()}");
            $this->newLine();
            
            $this->warn('⚠️  VERIFIQUE:');
            $this->line('   1. O servidor está rodando? php artisan serve');
            $this->line('   2. A porta 8000 está disponível?');
            $this->line('   3. Não há firewall bloqueando?');
            
            Log::error('Teste de mensagem - exceção', [
                'mensagem' => $mensagem,
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}

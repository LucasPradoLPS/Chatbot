<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessWhatsappMessage;
use App\Models\Agente;
use App\Models\InstanciaWhatsapp;

class TestarBotDireto extends Command
{
    protected $signature = 'bot:testar {mensagem?} {--numero=5511999999999}';
    protected $description = 'Testa o bot processando mensagem diretamente (sem HTTP)';

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║       TESTE DIRETO DO BOT (SEM SERVIDOR)             ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // Obter mensagem
        $mensagem = $this->argument('mensagem');
        if (!$mensagem) {
            $mensagem = $this->ask('Digite a mensagem para enviar ao bot', 'Olá, quero informações sobre apartamentos disponíveis');
        }

        $numero = $this->option('numero');
        $numeroNorm = $this->normalizeToE164($numero);
        
        $this->line('📝 DADOS DO TESTE:');
        $this->info("   • Mensagem: $mensagem");
        $this->info("   • Número: $numeroNorm");
        $this->newLine();

        // Verificar pré-requisitos
        $this->line('🔍 VERIFICANDO PRÉ-REQUISITOS...');
        
        $agente = Agente::where('ia_ativa', true)->first();
        if (!$agente) {
            $this->error('❌ Nenhum agente com IA ativa encontrado!');
            return 1;
        }
        $this->info("   ✅ Agente encontrado (ID: {$agente->id})");
        
        $instancia = InstanciaWhatsapp::first();
        if (!$instancia) {
            $this->error('❌ Nenhuma instância WhatsApp encontrada!');
            return 1;
        }
        $this->info("   ✅ Instância encontrada: {$instancia->instance_name}");
        
        $this->newLine();
        
        // Construir payload simulando webhook
        $payload = [
            'instance' => $instancia->instance_name,
            'data' => [
                'key' => [
                    'remoteJid' => $numeroNorm . '@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'TEST_' . uniqid(),
                ],
                'message' => [
                    'conversation' => $mensagem,
                ],
                'messageTimestamp' => time(),
                'pushName' => 'Teste',
            ],
            'event' => 'messages.upsert',
        ];

        $this->line('🚀 PROCESSANDO MENSAGEM...');
        $this->newLine();

        try {
            // Processar diretamente sem fila
            $remetente = $numeroNorm . '@s.whatsapp.net';
            $instance = $instancia->instance_name;
            $fromMe = false;
            $isGrupo = false;
            
            $this->line("   → Criando job de processamento...");
            
            // Criar e executar o job imediatamente
            $job = new ProcessWhatsappMessage(
                $payload,
                $remetente,
                null, // senderPn
                $instance,
                $fromMe,
                $isGrupo
            );
            
            $this->line("   → Executando processamento...");
            $this->line("   → Aguarde, isso pode levar alguns segundos...");
            $this->newLine();
            
            // Executar o job de forma síncrona
            $job->handle();
            
            $this->newLine();
            $this->info('✅ MENSAGEM PROCESSADA COM SUCESSO!');
            $this->newLine();
            
            // Aguardar um pouco para logs serem escritos
            sleep(2);
            
            // Mostrar últimos logs
            $this->line('📝 ÚLTIMOS LOGS:');
            $this->line(str_repeat('─', 80));
            
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $lastLines = array_slice($lines, -20);
                
                foreach ($lastLines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        // Destacar logs importantes
                        if (str_contains($line, 'ERROR')) {
                            $this->error('   ' . mb_substr($line, 0, 150));
                        } elseif (str_contains($line, 'WARNING')) {
                            $this->warn('   ' . mb_substr($line, 0, 150));
                        } elseif (str_contains($line, 'Resposta final da IA')) {
                            $this->info('   ' . mb_substr($line, 0, 150));
                        } elseif (str_contains($line, 'ProcessWhatsappMessage')) {
                            $this->line('   ' . mb_substr($line, 0, 150));
                        }
                    }
                }
            }
            
            $this->newLine();
            $this->info('✅ TESTE CONCLUÍDO!');
            $this->newLine();
            
            $this->line('💡 PRÓXIMOS PASSOS:');
            $this->line('   1. Veja todos os logs: php artisan debug:logs');
            $this->line('   2. Teste outra mensagem: php artisan bot:testar "sua mensagem"');
            $this->line('   3. Teste com outro número: php artisan bot:testar "msg" --numero=5511988887777');
            $this->newLine();
            
            Log::info('Teste direto executado com sucesso', [
                'mensagem' => $mensagem,
                'numero' => $numeroNorm,
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ ERRO NO PROCESSAMENTO!');
            $this->error("   • Erro: {$e->getMessage()}");
            $this->newLine();
            
            $this->warn('   Stack trace:');
            $this->line('   ' . $e->getTraceAsString());
            $this->newLine();
            
            Log::error('Teste direto falhou', [
                'mensagem' => $mensagem,
                'numero' => $numero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }

    private function normalizeToE164(?string $numero): ?string
    {
        if (!$numero) { return $numero; }
        $digits = preg_replace('/\D/', '', $numero);
        $country = (string) (config('app.whatsapp_country_code') ?? '55');
        if ($digits === '') { return $digits; }
        if (!str_starts_with($digits, $country)) {
            $digits = $country . $digits;
        }
        return $digits;
    }
}

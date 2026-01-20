<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Thread;

class ResetThreadCommand extends Command
{
    protected $signature = 'thread:reset {numero}';
    protected $description = 'Reseta uma thread de conversa para o começo';

    public function handle()
    {
        $numero = $this->argument('numero');
        
        $this->info("\n🔍 Procurando thread...\n");

        $thread = Thread::where('numero_cliente', $numero)->first();

        if ($thread) {
            $this->line("✅ Thread encontrada!");
            $this->line("   Número: {$thread->numero_cliente}");
            $this->line("   Etapa atual: {$thread->etapa_fluxo}");
            $this->line("   Objetivo: " . ($thread->objetivo ?? 'null'));
            $this->line("   LGPD: " . ($thread->lgpd_consentimento ? 'SIM' : 'NÃO') . "\n");
            
            $this->info("🔄 Resetando...\n");

            $thread->update([
                'etapa_fluxo' => 'boas_vindas',
                'objetivo' => null,
                'lgpd_consentimento' => false,
                'slots' => json_encode([]),
                'intent' => 'indefinido',
                'estado_atual' => 'STATE_START',
            ]);

            $this->info("✅ PRONTO!\n");
            $this->line("Estado novo:");
            $this->line("   Etapa: boas_vindas");
            $this->line("   Objetivo: null");
            $this->line("   LGPD: NÃO");
            $this->line("   Slots: {}\n");
            $this->warn("👉 Mande 'Olá' para o bot agora!\n");
        } else {
            $this->error("❌ Nenhuma thread encontrada para: $numero\n");
            $this->line("Threads existentes:");
            $threads = Thread::limit(10)->get();
            foreach ($threads as $t) {
                $this->line("   - {$t->numero_cliente} | Etapa: {$t->etapa_fluxo}");
            }
            $this->line("");
        }
    }
}

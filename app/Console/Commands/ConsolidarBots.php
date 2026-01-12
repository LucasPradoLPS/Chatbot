<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Agente;
use App\Models\Empresa;
use App\Models\Thread;
use App\Models\InstanciaWhatsapp;
use App\Models\AgenteGerado;

class ConsolidarBots extends Command
{
    protected $signature = 'bot:consolidar';
    protected $description = 'Consolida todos os bots em um único agente e empresa';

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║     CONSOLIDAÇÃO DE BOTS EM UM ÚNICO AGENTE         ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // 1. Listar estado atual
        $this->line('📊 ESTADO ATUAL:');
        $empresas = Empresa::all();
        $agentes = Agente::all();
        $threads = Thread::all();
        $instancias = InstanciaWhatsapp::all();

        $this->info("   • Empresas: {$empresas->count()}");
        $this->info("   • Agentes: {$agentes->count()}");
        $this->info("   • Threads: {$threads->count()}");
        $this->info("   • Instâncias WhatsApp: {$instancias->count()}");
        $this->newLine();

        // 2. Perguntar confirmação
        if (!$this->confirm('Deseja consolidar tudo em UMA empresa e UM agente?', true)) {
            $this->warn('Operação cancelada.');
            return 0;
        }

        $this->newLine();
        $this->line('🔄 Iniciando consolidação...');
        $this->newLine();

        DB::beginTransaction();

        try {
            // 3. Escolher ou criar empresa principal
            $empresaPrincipal = Empresa::first();
            
            if (!$empresaPrincipal) {
                $this->error('❌ Nenhuma empresa encontrada! Crie uma empresa primeiro.');
                return 1;
            }

            $this->info("✅ Empresa principal selecionada: {$empresaPrincipal->nome} (ID: {$empresaPrincipal->id})");

            // 4. Atualizar nome da empresa se for genérico
            if (in_array($empresaPrincipal->nome, ['Minha Empresa', 'Empresa'])) {
                $novoNome = $this->ask('Digite o nome da sua empresa', 'Chatbot Empresa');
                $empresaPrincipal->nome = $novoNome;
                $empresaPrincipal->save();
                $this->info("   → Nome atualizado para: {$novoNome}");
            }

            // 5. Consolidar agentes - manter apenas um
            $this->newLine();
            $this->line('🤖 CONSOLIDANDO AGENTES...');
            
            $agentePrincipal = Agente::where('empresa_id', $empresaPrincipal->id)
                ->where('ia_ativa', true)
                ->first();

            if (!$agentePrincipal) {
                // Criar um agente principal
                $agentePrincipal = Agente::create([
                    'empresa_id' => $empresaPrincipal->id,
                    'ia_ativa' => true,
                    'responder_grupo' => false,
                ]);
                $this->info("   ✅ Agente principal criado (ID: {$agentePrincipal->id})");
            } else {
                $this->info("   ✅ Agente principal mantido (ID: {$agentePrincipal->id})");
            }

            // 6. Atualizar todas as threads para a empresa principal
            $this->newLine();
            $this->line('💭 ATUALIZANDO THREADS...');
            $threadsAtualizadas = Thread::where('empresa_id', '!=', $empresaPrincipal->id)
                ->update(['empresa_id' => $empresaPrincipal->id]);
            $this->info("   ✅ {$threadsAtualizadas} threads migradas para empresa principal");

            // 7. Atualizar instâncias WhatsApp
            $this->newLine();
            $this->line('💬 ATUALIZANDO INSTÂNCIAS WHATSAPP...');
            $instanciasAtualizadas = InstanciaWhatsapp::where('empresa_id', '!=', $empresaPrincipal->id)
                ->update(['empresa_id' => $empresaPrincipal->id]);
            $this->info("   ✅ {$instanciasAtualizadas} instâncias migradas para empresa principal");

            // 8. Atualizar agentes gerados
            $this->newLine();
            $this->line('🧠 ATUALIZANDO AGENTES GERADOS (OpenAI)...');
            $agentesGeradosAtualizados = AgenteGerado::where('empresa_id', '!=', $empresaPrincipal->id)
                ->update(['empresa_id' => $empresaPrincipal->id]);
            $this->info("   ✅ {$agentesGeradosAtualizados} agentes gerados migrados");

            // 9. Deletar agentes duplicados (manter apenas o principal)
            $this->newLine();
            $this->line('🗑️  REMOVENDO AGENTES DUPLICADOS...');
            $agentesDeletados = Agente::where('id', '!=', $agentePrincipal->id)->delete();
            $this->info("   ✅ {$agentesDeletados} agentes duplicados removidos");

            // 10. Deletar empresas duplicadas
            $this->newLine();
            $this->line('🗑️  REMOVENDO EMPRESAS DUPLICADAS...');
            $empresasDeletadas = Empresa::where('id', '!=', $empresaPrincipal->id)->delete();
            $this->info("   ✅ {$empresasDeletadas} empresas duplicadas removidas");

            DB::commit();

            // Resumo final
            $this->newLine();
            $this->info('╔══════════════════════════════════════════════════════╗');
            $this->info('║            CONSOLIDAÇÃO CONCLUÍDA COM SUCESSO        ║');
            $this->info('╚══════════════════════════════════════════════════════╝');
            $this->newLine();

            $this->line('📊 RESULTADO FINAL:');
            $this->info("   • Empresa única: {$empresaPrincipal->nome} (ID: {$empresaPrincipal->id})");
            $this->info("   • Agente único: ID {$agentePrincipal->id} (IA: " . ($agentePrincipal->ia_ativa ? 'ATIVA' : 'INATIVA') . ")");
            $this->info("   • Total de threads: " . Thread::count());
            $this->info("   • Total de instâncias: " . InstanciaWhatsapp::count());
            $this->newLine();

            $this->info('✅ Todos os bots foram consolidados em um único agente!');
            $this->line('   Agora você tem uma estrutura limpa e organizada.');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ ERRO durante a consolidação: ' . $e->getMessage());
            $this->error('   Todas as alterações foram revertidas.');
            return 1;
        }
    }
}

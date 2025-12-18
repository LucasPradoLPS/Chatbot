<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstanciaWhatsapp;
use App\Models\Agente;

class ToggleBotGroupResponse extends Command
{
    protected $signature = 'bot:toggle-group 
                            {--instance= : Nome da instância WhatsApp} 
                            {--on : Habilitar resposta a grupos} 
                            {--off : Desabilitar resposta a grupos}';

    protected $description = 'Habilita ou desabilita respostas do bot em grupos para uma instância específica';

    public function handle()
    {
        $instanceName = $this->option('instance');
        $enable = $this->option('on');
        $disable = $this->option('off');

        if (!$instanceName) {
            $this->error('Você precisa especificar a instância usando --instance=NOME');
            return 1;
        }

        if (!$enable && !$disable) {
            $this->error('Você precisa especificar --on ou --off');
            return 1;
        }

        if ($enable && $disable) {
            $this->error('Você não pode usar --on e --off ao mesmo tempo');
            return 1;
        }

        $instancia = InstanciaWhatsapp::where('instance_name', $instanceName)->first();

        if (!$instancia) {
            $this->error("Instância '{$instanceName}' não encontrada");
            return 1;
        }

        $empresaId = $instancia->empresa_id;
        $valor = $enable ? true : false;

        $updated = Agente::where('empresa_id', $empresaId)
            ->update(['responder_grupo' => $valor]);

        if ($updated > 0) {
            $status = $valor ? 'habilitadas' : 'desabilitadas';
            $this->info("✓ Respostas a grupos {$status} para a instância '{$instanceName}' (Empresa ID: {$empresaId})");
            $this->info("  Agentes atualizados: {$updated}");
        } else {
            $this->warn("Nenhum agente encontrado para a empresa ID: {$empresaId}");
        }

        return 0;
    }
}

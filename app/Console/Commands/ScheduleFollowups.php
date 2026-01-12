<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FollowupSchedulerJob;

class ScheduleFollowups extends Command
{
    protected $signature = 'app:schedule-followups';
    protected $description = 'Executar verificação de follow-ups pendentes (2h e 24h)';

    public function handle()
    {
        $this->info('Iniciando scheduler de follow-ups...');
        
        dispatch(new FollowupSchedulerJob());
        
        $this->info('Job de follow-ups despachado com sucesso!');
        return 0;
    }
}

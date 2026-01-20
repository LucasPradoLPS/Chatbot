<?php

namespace App\Jobs;

use App\Services\FollowUpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFollowUpAutomaticly implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 300; // 5 minutos
    public int $retries = 2;
    
    private int $empresaId;
    
    public function __construct(int $empresaId)
    {
        $this->empresaId = $empresaId;
    }
    
    public function handle(): void
    {
        Log::info("Processando follow-ups automáticos", [
            'empresa_id' => $this->empresaId,
        ]);
        
        FollowUpService::procesarFollowUpsPendentes($this->empresaId);
    }
}

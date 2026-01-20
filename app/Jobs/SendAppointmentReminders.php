<?php

namespace App\Jobs;

use App\Services\AppointmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 300;
    public int $retries = 2;
    
    public function handle(): void
    {
        Log::info("Enviando lembretes de agendamentos");
        
        AppointmentService::enviarLembretes();
    }
}

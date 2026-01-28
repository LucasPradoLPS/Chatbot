<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Evitar que comandos long-running (serve / queue:work) sejam mortos pelo timeout.
        // Para web requests e comandos curtos, mantemos um limite razoável.
        if (!function_exists('set_time_limit')) {
            return;
        }

        $defaultLimitSeconds = 120;

        if (app()->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            $command = (string) ($argv[1] ?? '');

            $longRunningCommands = [
                'serve',
                'queue:work',
                'queue:listen',
                'schedule:work',
                'horizon',
            ];

            if (in_array($command, $longRunningCommands, true)) {
                @ini_set('max_execution_time', '0');
                set_time_limit(0);
                return;
            }

            set_time_limit($defaultLimitSeconds);
            return;
        }

        set_time_limit($defaultLimitSeconds);
    }
}

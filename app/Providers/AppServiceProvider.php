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
        // Aumentar timeout do PHP para jobs que podem levar tempo
        // Máximo necessário é ~60s (30 tentativas de polling + latência)
        if (function_exists('set_time_limit')) {
            set_time_limit(120); // 2 minutos de segurança
        }
    }
}

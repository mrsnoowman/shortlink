<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Ensure generated URLs (assets, Filament dynamic imports, etc.) use HTTPS in production.
        // This is especially important when SSL is terminated at a reverse proxy / load balancer.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}


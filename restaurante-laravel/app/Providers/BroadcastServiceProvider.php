<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // El mesero está autenticado con sesión web (auth:web),
        // así que el endpoint /broadcasting/auth debe usar ese guard.
        Broadcast::routes(['middleware' => ['web', 'auth:web']]);

        require base_path('routes/channels.php');
    }
}
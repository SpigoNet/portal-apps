<?php

namespace App\Modules\ComfyQueue;

use Illuminate\Support\ServiceProvider;

class ComfyQueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'ComfyQueue');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}

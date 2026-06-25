<?php

namespace App\Modules\Alfred;

use App\Modules\Alfred\Console\Commands\RelatorioManha;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AlfredServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Alfred';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware('web')
            ->group(__DIR__.'/routes.php');
    }

    public function register(): void
    {
        $this->commands([
            RelatorioManha::class,
        ]);
    }
}

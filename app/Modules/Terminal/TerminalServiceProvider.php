<?php

namespace App\Modules\Terminal;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TerminalServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Terminal';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web'])
            ->group(__DIR__.'/routes.php');
    }

    public function register(): void {}
}

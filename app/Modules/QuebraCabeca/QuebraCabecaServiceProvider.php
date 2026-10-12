<?php

namespace App\Modules\QuebraCabeca;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class QuebraCabecaServiceProvider extends ServiceProvider
{
    protected string $namespace = 'QuebraCabeca';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web'])
            ->group(__DIR__.'/routes.php');
    }

    public function register(): void {}
}

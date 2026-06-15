<?php

namespace App\Modules\Bingo;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BingoServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Bingo';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web'])
            ->group(__DIR__.'/routes.php');
    }

    public function register(): void {}
}

<?php

namespace App\Modules\VocabularioControlado;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class VocabularioControladoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'VocabularioControlado');

        Route::middleware('web')
            ->group(__DIR__.'/routes.php');
    }
}

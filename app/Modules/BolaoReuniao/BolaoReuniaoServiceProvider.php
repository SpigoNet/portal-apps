<?php

namespace App\Modules\BolaoReuniao;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BolaoReuniaoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'BolaoReuniao');
    }
}

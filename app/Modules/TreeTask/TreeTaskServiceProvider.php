<?php

namespace App\Modules\TreeTask;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TreeTaskServiceProvider extends ServiceProvider
{
    protected $namespace = 'TreeTask';

    public function boot()
    {
        // Carrega views com o namespace 'TreeTask::'
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        // Carrega rotas da API e Web
        Route::middleware('web')
            ->group(__DIR__.'/routes.php');
    }

    public function register()
    {
        //
    }
}

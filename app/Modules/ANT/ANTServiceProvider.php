<?php

namespace App\Modules\ANT;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ANTServiceProvider extends ServiceProvider
{
    /**
     * Namespace para os Controllers e Views
     */
    protected $namespace = 'App\Modules\ANT\Http\Controllers';

    public function boot()
    {
        // 1. Carregar Views (ex: view('ANT::index'))
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'ANT');

        // 2. Carregar Migrations (opcional, se quiser que fiquem dentro do módulo)
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // 3. Carregar Rotas
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(__DIR__ . '/routes.php');
    }

    public function register()
    {
        //
    }
}

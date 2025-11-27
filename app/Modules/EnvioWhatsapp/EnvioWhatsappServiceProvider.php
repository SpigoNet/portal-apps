<?php

namespace App\Modules\EnvioWhatsapp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EnvioWhatsappServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     * @var string
     */
    protected $namespace = 'EnvioWhatsapp';

    public function boot()
    {
        // Carrega as views do módulo
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        // Carrega as rotas
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }

    public function register()
    {
        //
    }
}

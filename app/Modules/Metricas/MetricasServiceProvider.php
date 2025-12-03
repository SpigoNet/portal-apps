<?php
namespace App\Modules\Metricas;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MetricasServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     */
    protected $namespace = 'Metricas';

    public function boot()
    {
        // Carrega as views
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

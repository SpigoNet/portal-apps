<?php

namespace App\Modules\StreamingManager;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StreamingManagerServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     * @var string
     */
    protected $namespace = 'StreamingManager';

    public function boot()
    {
        // Carrega as views do módulo com o namespace 'StreamingManager'
        $this->loadViewsFrom(__DIR__ . '/resources/views', $this->namespace);

        // Carrega as migrações do módulo
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Carrega as rotas web com middleware padrão
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/routes.php');
    }

    public function register()
    {
        //
    }
}

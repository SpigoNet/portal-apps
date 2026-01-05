<?php

namespace App\Modules\MundosDeMim;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MundosDeMimServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     * @var string
     */
    protected $namespace = 'MundosDeMim';

    public function boot()
    {
        // Carrega as views do módulo com o namespace 'MundosDeMim'
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        // Carrega as migrações do módulo
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Carrega as

        // Carrega as rotas web com middleware padrão
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/routes.php');

    }

    public function register()
    {
        //
    }
}

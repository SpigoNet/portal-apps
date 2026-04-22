<?php

namespace App\Modules\Mithril;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MithrilServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     *
     * @var string
     */
    protected $namespace = 'Mithril';

    public function boot()
    {
        // Carrega as views do módulo com o namespace 'Mithril'
        // Uso: view('Mithril::nome_da_view')
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        // Carrega as migrações do módulo (se houverem específicas, caso contrário usam-se as globais)
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Carrega as rotas web do módulo
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/routes.php');

        // Carrega as rotas de API do módulo
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/api.php');
    }

    public function register()
    {
        //
    }
}

<?php

namespace App\Modules\Mithril;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MithrilServiceProvider extends ServiceProvider
{
    /**
     * Define o namespace do módulo para as views.
     * @var string
     */
    protected $namespace = 'Mithril';

    public function boot()
    {
        // Carrega as views do módulo com o namespace 'Mithril'
        // Uso: view('Mithril::nome_da_view')
        $this->loadViewsFrom(__DIR__ . '/resources/views', $this->namespace);

        // Carrega as migrações do módulo (se houverem específicas, caso contrário usam-se as globais)
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Carrega o arquivo de rotas do módulo
        Route::middleware(['web', 'auth']) // Adicione 'auth' se o sistema exigir login
        ->group(__DIR__ . '/routes.php');
    }

    public function register()
    {
        //
    }
}

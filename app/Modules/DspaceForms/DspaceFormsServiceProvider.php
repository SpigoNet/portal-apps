<?php

namespace App\Modules\DspaceForms;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class DspaceFormsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Carrega as rotas do módulo
        Route::middleware('web')
            ->group(__DIR__.'/routes.php');

        // Carrega as migrations do módulo
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Define o namespace para as views (Ex: view('DspaceForms::index'))
        $this->loadViewsFrom(__DIR__.'/resources/views', 'DspaceForms');

        // View Composer para passar o config_id da sessão para o menu
        View::composer('DspaceForms::components.menu-main', function ($view) {
            $configId = null;
            if (Auth::check()) {
                $configId = session('dspace_config_id_'.Auth::id());
            }
            $view->with('configId', $configId);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}

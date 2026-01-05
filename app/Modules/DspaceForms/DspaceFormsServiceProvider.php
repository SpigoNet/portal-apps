<?php
namespace App\Modules\DspaceForms;

use Illuminate\Support\Facades\Route;
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
            ->group(__DIR__ . '/routes.php');

        //Carrega as migrations do módulo
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');


        // Define o namespace para as views (Ex: view('DspaceForms::index'))
        $this->loadViewsFrom(__DIR__.'/resources/views', 'DspaceForms');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}

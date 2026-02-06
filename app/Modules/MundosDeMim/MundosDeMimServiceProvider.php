<?php

namespace App\Modules\MundosDeMim;

use App\Models\PortalAppUser;
use Illuminate\Support\Facades\Gate;
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', $this->namespace);

        // Carrega as migrações do módulo
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Registra comandos de console
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\RechargeCredits::class,
            ]);
        }

        // Carrega as rotas web com middleware padrão
        Route::middleware(['web'])
            ->group(__DIR__ . '/routes.php');

        Gate::define('admin-do-app', function ($user) {
            return PortalAppUser::where('user_id', $user->id)
                ->where('portal_app_id', 10)
                ->where('role', 'admin')
                ->exists();
        });
    }

    public function register()
    {
        //
    }
}

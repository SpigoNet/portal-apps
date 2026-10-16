<?php

namespace App\Modules\Pidgey;

use App\Modules\Pidgey\Console\Commands\EnviarAgendados;
use App\Modules\Pidgey\Console\Commands\RegistrarWebhookTelegram;
use App\Modules\Pidgey\Services\ProvedoresConteudo;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PidgeyServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Pidgey';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware('api')
            ->prefix('api/pidgey')
            ->name('pidgey.api.')
            ->group(__DIR__.'/routes/api.php');

        Route::middleware(['web', 'auth'])
            ->prefix('pidgey')
            ->name('pidgey.')
            ->group(__DIR__.'/routes/web.php');
    }

    public function register(): void
    {
        $this->app->singleton(TelegramRoteador::class);
        $this->app->singleton(ProvedoresConteudo::class);

        $this->commands([
            EnviarAgendados::class,
            RegistrarWebhookTelegram::class,
        ]);
    }
}

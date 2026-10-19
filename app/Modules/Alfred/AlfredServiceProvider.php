<?php

namespace App\Modules\Alfred;

use App\Modules\Alfred\Console\Commands\EnviarMensagensAgendadas;
use App\Modules\Alfred\Console\Commands\RelatorioManha;
use App\Modules\Alfred\Services\Pidgey\ContextoDiaProvedor;
use App\Modules\Pidgey\Services\ProvedoresConteudo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AlfredServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Alfred';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware('web')
            ->group(__DIR__.'/routes.php');
    }

    public function register(): void
    {
        // Motivação do Luffy com o contexto do dia, enviada pelo Pidgey.
        $this->callAfterResolving(ProvedoresConteudo::class, fn (ProvedoresConteudo $p) => $p->registrar($this->app->make(ContextoDiaProvedor::class)));

        $this->commands([
            EnviarMensagensAgendadas::class,
            RelatorioManha::class,
        ]);
    }
}

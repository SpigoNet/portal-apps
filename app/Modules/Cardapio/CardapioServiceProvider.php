<?php

namespace App\Modules\Cardapio;

use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Services\Telegram\AgendamentoResumo;
use App\Modules\Cardapio\Services\Telegram\ResumoDiarioProvedor;
use App\Modules\Cardapio\Services\Telegram\SanjiTelegram;
use App\Modules\Pidgey\Events\TelegramVinculado;
use App\Modules\Pidgey\Services\ProvedoresConteudo;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CardapioServiceProvider extends ServiceProvider
{
    protected string $namespace = 'Cardapio';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web'])
            ->group(__DIR__.'/routes.php');

        // MCP para agentes externos (stateless, token): /cardapio/mcp
        Route::group([], __DIR__.'/mcp.php');

        // O resumo diário do Sanji é um agendamento do Pidgey, criado ao vincular o Telegram ou ao entrar numa casa.
        Event::listen(TelegramVinculado::class, fn (TelegramVinculado $e) => $this->app->make(AgendamentoResumo::class)->garantir($e->userId));
        Pessoa::saved(function (Pessoa $pessoa): void {
            if ($pessoa->user_id !== null && ($pessoa->wasRecentlyCreated || $pessoa->wasChanged(['user_id', 'ativo']))) {
                $this->app->make(AgendamentoResumo::class)->garantir((int) $pessoa->user_id);
            }
        });
    }

    public function register(): void
    {
        $this->callAfterResolving(ProvedoresConteudo::class, fn (ProvedoresConteudo $p) => $p->registrar($this->app->make(ResumoDiarioProvedor::class)));

        // O bot do Cardápio (Sanji) é atendido pelo canal Telegram do Pidgey.
        $this->callAfterResolving(TelegramRoteador::class, fn (TelegramRoteador $r) => $r->registrar($this->app->make(SanjiTelegram::class)));
    }
}

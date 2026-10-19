<?php

namespace App\Modules\TreeTask;

use App\Models\PortalApp;
use App\Modules\Pidgey\Services\ProvedoresConteudo;
use App\Modules\TreeTask\Services\Pidgey\ResumoTarefasProvedor;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TreeTaskServiceProvider extends ServiceProvider
{
    protected $namespace = 'TreeTask';

    public function boot()
    {
        // Carrega views com o namespace 'TreeTask::'
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        // Apps do portal para a troca de módulos (mesma regra de visibilidade do menu global).
        View::composer('TreeTask::components.layout', function ($view): void {
            $userId = auth()->id();

            $view->with('appsPortal', $userId === null ? collect() : PortalApp::query()
                ->where('visibility', 'public')
                ->orWhere('visibility', 'private')
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $userId))
                ->orderBy('title')
                ->get());
        });

        // Carrega rotas da API e Web
        Route::middleware('web')
            ->group(__DIR__.'/routes.php');
    }

    public function register()
    {
        // Resumos de tarefas que o Pidgey envia pelas personas (Zoro).
        $this->callAfterResolving(ProvedoresConteudo::class, function (ProvedoresConteudo $p): void {
            foreach ([ResumoTarefasProvedor::TIPO_MANHA, ResumoTarefasProvedor::TIPO_FECHAMENTO] as $tipo) {
                $p->registrar($this->app->make(ResumoTarefasProvedor::class, ['tipo' => $tipo]));
            }
        });
    }
}

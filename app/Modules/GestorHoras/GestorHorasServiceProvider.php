<?php
namespace App\Modules\GestorHoras;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class GestorHorasServiceProvider extends ServiceProvider
{
    protected $namespace = 'GestorHoras';

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);

        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');

        // --- DEFINIÇÃO DE PERMISSÕES (GATES) ---

        // 1. Admin: Pode gerenciar clientes e usuários
        Gate::define('gh.admin', function (User $user) {
            return $user->gh_role === 'admin';
        });

        // 2. Operacional (Admin + Dev): Pode criar contratos e apontar horas
        Gate::define('gh.operacional', function (User $user) {
            return in_array($user->gh_role, ['admin', 'dev']);
        });

        // 3. Visualizar Contratos (Todos):
        // Admin e Dev veem tudo. Cliente vê apenas os seus.
        // Essa lógica será aplicada na Query do Controller, mas o Gate valida o acesso ao módulo.
        Gate::define('gh.acessar', function (User $user) {
            return in_array($user->gh_role, ['admin', 'dev', 'client']);
        });
    }

    public function register()
    {
        //
    }
}

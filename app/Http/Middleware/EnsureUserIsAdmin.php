<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalApp;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // --- PONTO DE VERIFICAÇÃO 1 ---
        // Vamos garantir que estamos procurando pelo link EXATO que está no seeder.
        $adminApp = PortalApp::where('start_link', '/admin/apps')->first();

        // **DEBUG**: Se quiser confirmar que o app está sendo encontrado, descomente a linha abaixo.
        // A página vai parar e mostrar os dados do App de Admin ou 'null'.
        // dd($adminApp);

        if (!$adminApp) {
            // Se o app não for encontrado, é uma falha de configuração.
            return redirect()->route('dashboard')->with('error', 'O aplicativo de administração não foi encontrado no banco de dados.');
        }

        // --- PONTO DE VERIFICAÇÃO 2 ---
        // Esta é a verificação mais crucial. Ela olha na tabela 'portal_app_user'.
        // Usando o próprio relacionamento, perguntamos: "Este usuário possui este app específico?"
        $userIsAdmin = Auth::user()->portalApps->contains($adminApp);

        // **DEBUG**: Descomente a linha abaixo para ver o resultado (true ou false) da verificação.
        // Se aqui retornar 'false', mesmo o app aparecendo na lista, há algo muito estranho.
        // dd($userIsAdmin);

        if (!$userIsAdmin) {
            // Se o usuário não tem a permissão, redireciona com a mensagem de erro.
            return redirect()->route('dashboard')->with('error', 'Acesso não autorizado a esta área.');
        }

        // Se passou em todas as verificações, o usuário é um admin. Pode seguir.
        return $next($request);
    }
}


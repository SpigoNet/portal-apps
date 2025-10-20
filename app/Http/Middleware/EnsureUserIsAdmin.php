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

        $adminApp = PortalApp::where('start_link', '/admin/apps')->first();


        if (!$adminApp) {
            return redirect()->route('dashboard')->with('error', 'O aplicativo de administração não foi encontrado no banco de dados.');
        }

        // --- LÓGICA CORRIGIDA ---
        // Em vez de carregar todos os apps do usuário para a memória e depois verificar,
        // esta abordagem faz uma pergunta direta e eficiente ao banco de dados:
        // "Na relação 'portalApps' deste usuário, existe um registro com o ID do app de admin?"
        // Isso evita problemas de comparação de objetos e é mais performático.
        $userIsAdmin = Auth::user()->portalApps()->where('portal_app_id', $adminApp->id)->exists();

        // **DEBUG**: Se ainda precisar, descomente a linha abaixo para ver o resultado (true ou false).
        // dd($userIsAdmin);

        if (!$userIsAdmin) {
            return redirect()->route('dashboard')->with('error', 'Acesso não autorizado a esta área.');
        }

        return $next($request);
    }
}


<?php

namespace App\Http\Middleware;

use App\Models\PortalAppUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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


        $linkAppAtual = explode('/',request()->route()->uri)[0];
        $appAtual = PortalApp::where('start_link', '/' . $linkAppAtual)->first();


        if (!$appAtual) {
            return redirect()->route('welcome')->with('error', 'O aplicativo atual não foi encontrado.');
        }

        // --- LÓGICA CORRIGIDA ---
        // Em vez de carregar todos os apps do usuário para a memória e depois verificar,
        // esta abordagem faz uma pergunta direta e eficiente ao banco de dados:
        // "Na relação 'portalApps' deste usuário, existe um registro com o ID do app de admin?"
        // Isso evita problemas de comparação de objetos e é mais performático.
        $userIsAdmin = PortalAppUser::query()
            ->where('portal_app_id', $appAtual->id)
            ->where('user_id', Auth::id())
            ->where('role', 'admin')
            ->exists();

        if (!$userIsAdmin) {
            return redirect()->route('welcome')->with('error', 'Acesso não autorizado a esta área.');
        }
        view()->share('isAppAdmin', $userIsAdmin);

        return $next($request);
    }
}


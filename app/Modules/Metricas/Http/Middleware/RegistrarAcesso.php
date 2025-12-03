<?php

namespace App\Modules\Metricas\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Metricas\Models\MetricaAcesso;
use Illuminate\Support\Facades\Auth;

class RegistrarAcesso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $nomeModulo): Response
    {
        // Apenas registra se o usuário estiver logado (opcional, remova o if se quiser registrar anônimos)
        if (Auth::check()) {
            MetricaAcesso::create([
                'user_id' => Auth::id(),
                'modulo_nome' => $nomeModulo,
                'url_acessada' => $request->fullUrl(),
                'metodo_http' => $request->method(),
                'ip_origem' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}

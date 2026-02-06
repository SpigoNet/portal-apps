<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetModuleContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se a URL começar com mundos-de-mim, salvamos na sessão
        if ($request->is('mundos-de-mim*')) {
            Session::put('module_origin', 'mundos-de-mim');

            // Também salvamos a URL exata para o 'intended' customizado se necessário
            if ($request->method() === 'GET' && !$request->routeIs('mundos-de-mim.landing')) {
                Session::put('module_last_url', $request->fullUrl());
            }
        }

        return $next($request);
    }
}

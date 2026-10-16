<?php

namespace App\Modules\Cardapio\Http\Middleware;

use App\Modules\Cardapio\Models\Pessoa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a casa do usuário logado (via Pessoa ativa com o seu user_id) e a expõe em
 * $request->attributes['casa']. Nunca confia em casa_id vindo do request.
 */
class ResolverCasa
{
    public function handle(Request $request, Closure $next): Response
    {
        $pessoa = Pessoa::with('casa')
            ->where('user_id', $request->user()->id)
            ->where('ativo', true)
            ->first();

        if ($pessoa === null) {
            return redirect()->route('cardapio.casa.criar');
        }

        $request->attributes->set('casa', $pessoa->casa);
        $request->attributes->set('pessoaAtual', $pessoa);

        return $next($request);
    }
}

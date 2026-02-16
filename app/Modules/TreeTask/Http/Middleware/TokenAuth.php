<?php

namespace App\Modules\TreeTask\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class TokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('X-User-ID');
        $token = $request->header('X-Token');

        if (! $userId || ! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais não fornecidas. Envie X-User-ID e X-Token no header.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 401);
        }

        // Gera o token esperado: MD5(email + senha_criptografada)
        $expectedToken = md5($user->email.$user->password);

        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.',
            ], 401);
        }

        // Autentica o usuário para a requisição
        auth()->setUser($user);

        return $next($request);
    }
}

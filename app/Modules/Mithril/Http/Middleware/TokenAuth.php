<?php

namespace App\Modules\Mithril\Http\Middleware;

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

        $expectedToken = md5($user->email.$user->password);

        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.',
            ], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}

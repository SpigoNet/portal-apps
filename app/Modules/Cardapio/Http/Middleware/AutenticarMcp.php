<?php

namespace App\Modules\Cardapio\Http\Middleware;

use App\Models\User;
use App\Modules\Cardapio\Models\Pessoa;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica agentes externos no MCP do Cardápio com o mesmo token dos demais módulos
 * (`X-User-ID` + `X-Token`, `Authorization: Bearer <id>:<token>` ou na própria URL `/cardapio/mcp/{id}/{token}`) e resolve a casa pela
 * Pessoa ativa do usuário. Nunca aceita casa_id vindo do request.
 */
class AutenticarMcp
{
    public static function tokenDe(User $usuario): string
    {
        return md5($usuario->email.$usuario->password);
    }

    public function handle(Request $request, Closure $next): Response
    {
        [$userId, $token] = $this->credenciais($request);

        if ($userId === null || $token === null) {
            return $this->erro('Credenciais não fornecidas. Envie X-User-ID e X-Token (ou Authorization: Bearer <id>:<token>).');
        }

        $usuario = User::find($userId);

        if ($usuario === null || ! hash_equals(self::tokenDe($usuario), $token)) {
            return $this->erro('Credenciais inválidas.');
        }

        $pessoa = Pessoa::with('casa')->where('user_id', $usuario->id)->where('ativo', true)->first();

        if ($pessoa === null) {
            return $this->erro('Este usuário não pertence a nenhuma casa do Cardápio.', 403);
        }

        auth()->setUser($usuario);
        $request->attributes->set('casa', $pessoa->casa);
        $request->attributes->set('pessoaAtual', $pessoa);

        return $next($request);
    }

    /**
     * @return array{0: ?int, 1: ?string}
     */
    private function credenciais(Request $request): array
    {
        $userId = $request->header('X-User-ID') ?? $request->route('userId');
        $token = $request->header('X-Token') ?? $request->route('token');

        if (($userId === null || $token === null) && preg_match('/^Bearer\s+(\d+):(\w+)$/', (string) $request->header('Authorization'), $m)) {
            [$userId, $token] = [$m[1], $m[2]];
        }

        return [ctype_digit((string) $userId) ? (int) $userId : null, is_string($token) && $token !== '' ? $token : null];
    }

    private function erro(string $mensagem, int $status = 401): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => ['code' => -32001, 'message' => $mensagem],
        ], $status);
    }
}

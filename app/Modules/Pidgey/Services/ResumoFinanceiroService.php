<?php

namespace App\Modules\Pidgey\Services;

use App\Models\User;
use App\Modules\Mithril\Http\Controllers\Api\RelatorioMarkdownController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResumoFinanceiroService
{
    /**
     * Obtém o relatório financeiro do Mithril (relatorio-markdown) para o
     * usuário configurado em services.pidgey.financeiro_user_id.
     *
     * Reutiliza o controller da API do Mithril, autenticando internamente
     * como o usuário dono dos dados (respeitando o global scope por user_id).
     */
    public function obterRelatorio(int $mes, int $ano): string
    {
        $userId = (int) config('services.pidgey.financeiro_user_id', 1);
        $user = User::find($userId);

        if (! $user) {
            return '';
        }

        $anterior = Auth::user();
        Auth::setUser($user);

        try {
            $request = Request::create('/api/mithril/relatorio-markdown', 'GET', [
                'mes' => $mes,
                'ano' => $ano,
            ]);

            /** @var \Illuminate\Http\Response $response */
            $response = app(RelatorioMarkdownController::class)->index($request);

            return $response->getContent();
        } finally {
            if ($anterior) {
                Auth::setUser($anterior);
            }
        }
    }

    /**
     * Extrai o Saldo Acumulado (Efetivado) do relatório markdown.
     * Retorna null se não encontrar. Valores negativos são preservados.
     */
    public function saldoEfetivado(string $markdown): ?float
    {
        if (! preg_match('/Saldo Acumulado \(Efetivado\)\s*\|\s*R\$\s*(-?[\d.]+,[\d]{2})/', $markdown, $m)) {
            return null;
        }

        $numero = str_replace(['.', ','], ['', '.'], $m[1]);

        return (float) $numero;
    }
}

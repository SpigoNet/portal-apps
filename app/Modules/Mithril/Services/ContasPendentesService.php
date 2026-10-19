<?php

namespace App\Modules\Mithril\Services;

use App\Modules\Mithril\Models\CartaoFaturaItem;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Contas a pagar do mês que ainda não foram efetivadas: despesas planejadas (pré-transações) e faturas de
 * cartão em aberto. Recebe o usuário explicitamente porque roda fora de uma requisição (os models usam um
 * escopo global baseado em `auth()`).
 */
class ContasPendentesService
{
    public const JANELA_DIAS = 3;

    /**
     * Cada item: ['descricao' => string, 'valor' => float, 'vencimento' => Carbon, 'tipo' => 'conta'|'fatura'].
     *
     * @return array{atrasadas: Collection, hoje: Collection, proximas: Collection}
     */
    public function para(int $userId, ?Carbon $hoje = null): array
    {
        $hoje = ($hoje ?? now())->copy()->startOfDay();

        $contas = $this->despesasPlanejadas($userId, $hoje)->merge($this->faturasEmAberto($userId, $hoje));
        $limite = $hoje->copy()->addDays(self::JANELA_DIAS);

        $contas = $contas->sortBy(fn (array $c) => $c['vencimento']->timestamp);

        return [
            'atrasadas' => $contas->filter(fn (array $c) => $c['vencimento']->lt($hoje))->values(),
            'hoje' => $contas->filter(fn (array $c) => $c['vencimento']->isSameDay($hoje))->values(),
            'proximas' => $contas->filter(fn (array $c) => $c['vencimento']->gt($hoje) && $c['vencimento']->lte($limite))->values(),
        ];
    }

    /**
     * @return Collection<int, array{descricao: string, valor: float, vencimento: Carbon, tipo: string}>
     */
    private function despesasPlanejadas(int $userId, Carbon $hoje): Collection
    {
        $inicioMes = $hoje->copy()->startOfMonth();
        $fimMes = $hoje->copy()->endOfMonth();

        $efetivadas = Transacao::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereBetween('data_efetiva', [$inicioMes, $fimMes])
            ->whereNotNull('pre_transacao_id')
            ->pluck('pre_transacao_id')
            ->all();

        return PreTransacao::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('ativa', true)
            ->where('valor_parcela', '<', 0)
            ->get()
            ->reject(fn (PreTransacao $pt) => in_array($pt->id, $efetivadas, true))
            ->reject(fn (PreTransacao $pt) => $pt->data_ultima_acao !== null && $pt->data_ultima_acao->isSameMonth($hoje))
            ->filter(fn (PreTransacao $pt) => $this->valeNoMes($pt, $hoje))
            ->map(fn (PreTransacao $pt) => [
                'descricao' => (string) $pt->descricao,
                'valor' => abs((float) $pt->valor_parcela),
                'vencimento' => $this->diaDoMes($hoje, (int) $pt->dia_vencimento),
                'tipo' => 'conta',
            ])
            ->toBase()
            ->values();
    }

    private function valeNoMes(PreTransacao $pt, Carbon $hoje): bool
    {
        $inicio = $pt->data_inicio->copy()->startOfMonth();

        if ($inicio->gt($hoje->copy()->endOfMonth())) {
            return false;
        }

        if ($pt->tipo === 'parcelada') {
            $parcela = $inicio->diffInMonths($hoje->copy()->startOfMonth(), false);

            return $parcela >= 0 && $parcela < (int) $pt->total_parcelas;
        }

        return true;
    }

    /**
     * Fatura fechada de cada cartão que ainda não foi paga: itens sem `fatura_id` comprados até o último
     * fechamento (a `Fatura` só nasce quando é paga). Exige `dia_fechamento` e `dia_vencimento` no cartão.
     *
     * @return Collection<int, array{descricao: string, valor: float, vencimento: Carbon, tipo: string}>
     */
    private function faturasEmAberto(int $userId, Carbon $hoje): Collection
    {
        return Conta::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('tipo', 'credito')
            ->whereNotNull('dia_fechamento')
            ->whereNotNull('dia_vencimento')
            ->get()
            ->map(function (Conta $cartao) use ($userId, $hoje): ?array {
                $fechamento = $this->ultimoFechamento($hoje, (int) $cartao->dia_fechamento);
                $total = (float) CartaoFaturaItem::withoutGlobalScopes()
                    ->where('user_id', $userId)
                    ->where('conta_id', $cartao->id)
                    ->whereNull('fatura_id')
                    ->whereDate('data_compra', '<=', $fechamento->toDateString())
                    ->sum('valor');

                // Os lançamentos do cartão são negativos; saldo zerado ou a favor não há o que pagar.
                if ($total >= 0) {
                    return null;
                }

                // Vence no mesmo mês do fechamento se o dia de vencimento vier depois; senão, no mês seguinte.
                $mesVencimento = (int) $cartao->dia_vencimento > (int) $cartao->dia_fechamento ? $fechamento->copy() : $fechamento->copy()->addMonthNoOverflow();

                return [
                    'descricao' => 'Fatura '.$cartao->nome,
                    'valor' => abs($total),
                    'vencimento' => $this->diaDoMes($mesVencimento, (int) $cartao->dia_vencimento),
                    'tipo' => 'fatura',
                ];
            })
            ->toBase()
            ->filter()
            ->values();
    }

    /**
     * Data do fechamento mais recente (hoje inclusive) para o dia de fechamento do cartão.
     */
    private function ultimoFechamento(Carbon $hoje, int $diaFechamento): Carbon
    {
        $mes = $this->diaDoMes($hoje, $diaFechamento);

        return $mes->gt($hoje) ? $this->diaDoMes($hoje->copy()->subMonthNoOverflow(), $diaFechamento) : $mes;
    }

    private function diaDoMes(Carbon $referencia, int $dia): Carbon
    {
        $ultimo = $referencia->daysInMonth;

        return $referencia->copy()->startOfMonth()->addDays(min(max($dia, 1), $ultimo) - 1)->startOfDay();
    }
}

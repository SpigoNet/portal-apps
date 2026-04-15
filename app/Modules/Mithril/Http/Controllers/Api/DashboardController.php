<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\CartaoFaturaItem;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoje = Carbon::now();
        $inicioMes = $hoje->copy()->startOfMonth();
        $fimMes = $hoje->copy()->endOfMonth();
        $mesAnterior = $hoje->copy()->subMonth();

        $contas = Conta::all();

        $saldosFechamento = SaldoFechamento::where('ano', $mesAnterior->year)
            ->where('mes', $mesAnterior->month)
            ->pluck('saldo_final', 'conta_id');

        $transacoesMes = Transacao::whereBetween('data_efetiva', [$inicioMes, $fimMes])->get();

        $idsPreTransacoesEfetivadas = $transacoesMes->pluck('pre_transacao_id')->filter()->toArray();

        $preTransacoes = PreTransacao::where('ativa', true)
            ->where(function ($query) {
                $query->where('tipo', 'recorrente')
                    ->orWhere(function ($q) {
                        $q->where('tipo', 'parcelada')
                            ->whereColumn('parcela_atual', '<', 'total_parcelas');
                    });
            })
            ->whereNotIn('id', $idsPreTransacoesEfetivadas)
            ->get();

        $dadosContas = [];
        $dadosCartoes = [];

        foreach ($contas as $conta) {
            $saldoInicial = $saldosFechamento[$conta->id] ?? $conta->saldo_inicial;

            if ($conta->tipo === 'normal') {
                $realHoje = $saldoInicial;
                $previstoHoje = $saldoInicial;
                $realFimMes = $saldoInicial;
                $previstoFimMes = $saldoInicial;

                foreach ($transacoesMes->where('conta_id', $conta->id) as $t) {
                    $valor = $t->valor;
                    $realFimMes += $valor;
                    $previstoFimMes += $valor;

                    if ($t->data_efetiva <= $hoje) {
                        $realHoje += $valor;
                        $previstoHoje += $valor;
                    }
                }

                foreach ($preTransacoes->where('conta_id', $conta->id) as $pt) {
                    $dataVencimento = Carbon::create($hoje->year, $hoje->month, $pt->dia_vencimento);
                    $valor = $pt->valor_parcela;

                    $previstoFimMes += $valor;

                    if ($dataVencimento <= $hoje) {
                        $previstoHoje += $valor;
                    }
                }

                $dadosContas[] = [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo,
                    'saldo_inicial' => (float) $saldoInicial,
                    'real_hoje' => (float) $realHoje,
                    'previsto_hoje' => (float) $previstoHoje,
                    'real_fim_mes' => (float) $realFimMes,
                    'previsto_fim_mes' => (float) $previstoFimMes,
                ];
            } elseif ($conta->tipo === 'credito') {
                $faturaAberta = CartaoFaturaItem::where('conta_id', $conta->id)
                    ->whereNull('fatura_id')
                    ->sum('valor');

                $totalPagar = $saldoInicial + $faturaAberta;

                $dadosCartoes[] = [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'tipo' => $conta->tipo,
                    'saldo_anterior' => (float) $saldoInicial,
                    'fatura_aberta' => (float) $faturaAberta,
                    'total_pagar' => (float) $totalPagar,
                ];
            }
        }

        return response()->json([
            'data' => [
                'periodo' => [
                    'mes' => $hoje->month,
                    'ano' => $hoje->year,
                    'label' => $hoje->translatedFormat('F Y'),
                ],
                'contas' => $dadosContas,
                'cartoes' => $dadosCartoes,
                'transacoes_mes' => $transacoesMes->count(),
                'pre_transacoes_pendentes' => $preTransacoes->count(),
            ],
        ]);
    }
}
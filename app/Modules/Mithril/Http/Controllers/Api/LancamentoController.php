<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LancamentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        $contaId = $request->input('conta_id');

        $dataBase = Carbon::createFromDate($ano, $mes, 1);
        $inicioMes = $dataBase->copy()->startOfMonth();
        $fimMes = $dataBase->copy()->endOfMonth();
        $dataMesAnterior = $dataBase->copy()->subMonth();
        $saldoInicial = 0;

        $queryContas = Conta::where('tipo', 'normal');
        if ($contaId) {
            $queryContas->where('id', $contaId);
        }
        $contasEnvolvidas = $queryContas->get();

        foreach ($contasEnvolvidas as $c) {
            $fechamento = SaldoFechamento::where('conta_id', $c->id)
                ->where('ano', $dataMesAnterior->year)
                ->where('mes', $dataMesAnterior->month)
                ->first();

            $saldoInicial += $fechamento ? $fechamento->saldo_final : $c->saldo_inicial;
        }

        $transacoesQuery = Transacao::with('conta')
            ->whereBetween('data_efetiva', [$inicioMes, $fimMes]);

        if ($contaId) {
            $transacoesQuery->where('conta_id', $contaId);
        }

        $transacoes = $transacoesQuery->orderBy('data_efetiva')->get();

        $preTransacoesQuery = PreTransacao::with('conta')->where('ativa', true);
        if ($contaId) {
            $preTransacoesQuery->where('conta_id', $contaId);
        }
        $preTransacoesTodas = $preTransacoesQuery->get();
        $lancamentosProjetados = collect();

        $idsJaEfetivados = $transacoes->pluck('pre_transacao_id')->filter()->toArray();

        foreach ($preTransacoesTodas as $pt) {
            if (in_array($pt->id, $idsJaEfetivados)) {
                continue;
            }

            $parcelaAtualCalculada = null;

            if ($pt->tipo === 'recorrente') {
                if ($pt->data_inicio->startOfMonth()->gt($fimMes)) {
                    continue;
                }
            } elseif ($pt->tipo === 'parcelada') {
                $diffMeses = $this->diffEmMeses($pt->data_inicio, $dataBase);
                if ($diffMeses < 0 || $diffMeses >= $pt->total_parcelas) {
                    continue;
                }
                $parcelaAtualCalculada = $diffMeses + 1;
            }

            $dataPrevista = Carbon::create($ano, $mes, $pt->dia_vencimento);
            if ($dataPrevista->month != $mes) {
                $dataPrevista = Carbon::create($ano, $mes, 1)->endOfMonth();
            }

            $lancamentosProjetados->push((object) [
                'id' => null,
                'pre_transacao_id' => $pt->id,
                'data_efetiva' => $dataPrevista,
                'descricao' => $pt->descricao,
                'conta' => $pt->conta,
                'valor' => $pt->valor_parcela,
                'status' => $this->getStatus($pt, $dataBase),
                'tipo' => 'projetado',
                'meta_parcela' => $parcelaAtualCalculada ? "{$parcelaAtualCalculada}/{$pt->total_parcelas}" : null,
            ]);
        }

        $lancamentosReais = $transacoes->map(fn ($t) => (object) [
            'id' => $t->id,
            'pre_transacao_id' => $t->pre_transacao_id,
            'data_efetiva' => $t->data_efetiva,
            'descricao' => $t->descricao,
            'conta' => $t->conta,
            'valor' => $t->valor,
            'status' => 'efetivado',
            'tipo' => 'real',
            'meta_parcela' => null,
        ]);

        $listaCombinada = $lancamentosReais->merge($lancamentosProjetados)->sortBy('data_efetiva')->values();

        $acumuladoEfetivado = $saldoInicial;
        $acumuladoPrevisto = $saldoInicial;

        foreach ($listaCombinada as $item) {
            $acumuladoPrevisto += $item->valor;

            if ($item->status === 'efetivado') {
                $acumuladoEfetivado += $item->valor;
            }

            $item->saldo_acumulado_efetivado = $acumuladoEfetivado;
            $item->saldo_acumulado_previsto = $acumuladoPrevisto;
        }

        $contas = Conta::all();

        return response()->json([
            'data' => $listaCombinada->map(fn ($item) => [
                'id' => $item->id,
                'pre_transacao_id' => $item->pre_transacao_id,
                'descricao' => $item->descricao,
                'valor' => (float) $item->valor,
                'data_efetiva' => $item->data_efetiva instanceof \Carbon\Carbon
                    ? $item->data_efetiva->format('Y-m-d')
                    : $item->data_efetiva,
                'conta' => $item->conta ? [
                    'id' => $item->conta->id,
                    'nome' => $item->conta->nome,
                ] : null,
                'status' => $item->status,
                'tipo' => $item->tipo,
                'meta_parcela' => $item->meta_parcela,
                'saldo_acumulado_efetivado' => (float) $item->saldo_acumulado_efetivado,
                'saldo_acumulado_previsto' => (float) $item->saldo_acumulado_previsto,
            ]),
            'meta' => [
                'mes' => (int) $mes,
                'ano' => (int) $ano,
                'saldo_inicial' => (float) $saldoInicial,
                'saldo_acumulado_efetivado' => (float) $acumuladoEfetivado,
                'saldo_acumulado_previsto' => (float) $acumuladoPrevisto,
                'total_registros' => $listaCombinada->count(),
            ],
        ]);
    }

    private function diffEmMeses($dataInicio, $dataAtual): int
    {
        $inicio = Carbon::parse($dataInicio)->startOfMonth();
        $atual = Carbon::parse($dataAtual)->startOfMonth();

        return $inicio->diffInMonths($atual, false);
    }

    private function getStatus($pt, $dataMesAtual): string
    {
        if ($pt->data_ultima_acao) {
            $ultimaAcao = Carbon::parse($pt->data_ultima_acao);
            if ($ultimaAcao->format('Y-m') === $dataMesAtual->format('Y-m')) {
                return 'confirmado';
            }
        }

        return 'pendente';
    }
}
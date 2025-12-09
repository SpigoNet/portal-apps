<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\SaldoFechamento; // Adicionado
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LancamentoController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filtros
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        $contaId = $request->input('conta_id');

        $dataBase = Carbon::createFromDate($ano, $mes, 1);
        $inicioMes = $dataBase->copy()->startOfMonth();
        $fimMes = $dataBase->copy()->endOfMonth();

        // --- NOVO: Cálculo do Saldo Inicial ---
        // Busca o fechamento do mês anterior ou o saldo inicial da conta
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
        // --------------------------------------

        // 2. Busca Transações EFETIVADAS
        $transacoesQuery = Transacao::with('conta')
            ->whereBetween('data_efetiva', [$inicioMes, $fimMes]);

        if ($contaId) {
            $transacoesQuery->where('conta_id', $contaId);
        }

        $transacoes = $transacoesQuery->orderBy('data_efetiva')->get();

        // 3. Busca Pré-Transações (Projeções)
        $preTransacoesQuery = PreTransacao::with('conta')->where('ativa', true);

        if ($contaId) {
            $preTransacoesQuery->where('conta_id', $contaId);
        }

        $preTransacoesTodas = $preTransacoesQuery->get();
        $lancamentosProjetados = collect();

        $idsJaEfetivados = $transacoes->pluck('pre_transacao_id')->filter()->toArray();

        foreach ($preTransacoesTodas as $pt) {
            if (in_array($pt->id, $idsJaEfetivados)) continue;

            $parcelaAtualCalculada = null;

            if ($pt->tipo === 'recorrente') {
                if ($pt->data_inicio->startOfMonth()->gt($fimMes)) continue;
            } elseif ($pt->tipo === 'parcelada') {
                $diffMeses = $this->diffEmMeses($pt->data_inicio, $dataBase);
                if ($diffMeses < 0 || $diffMeses >= $pt->total_parcelas) continue;
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

        // 4. Merge e Ordenação
        $lancamentosReais = $transacoes->toBase()->map(function ($t) {
            return (object) [
                'id' => $t->id,
                'pre_transacao_id' => $t->pre_transacao_id,
                'data_efetiva' => $t->data_efetiva,
                'descricao' => $t->descricao,
                'conta' => $t->conta,
                'valor' => $t->valor,
                'status' => 'efetivado',
                'tipo' => 'real',
                'meta_parcela' => null,
            ];
        });

        // Ordena por data e reseta as chaves para iterar corretamente
        $listaCombinada = $lancamentosReais->merge($lancamentosProjetados)
            ->sortBy('data_efetiva')
            ->values();

        // --- NOVO: Calcular Acumulados ---
        $acumuladoEfetivado = $saldoInicial;
        $acumuladoPrevisto = $saldoInicial;

        foreach ($listaCombinada as $item) {
            // Previsto sempre soma tudo
            $acumuladoPrevisto += $item->valor;

            // Efetivado só soma se já aconteceu
            if ($item->status === 'efetivado') {
                $acumuladoEfetivado += $item->valor;
            }

            // Anexa os valores ao objeto para usar na View
            $item->saldo_acumulado_efetivado = $acumuladoEfetivado;
            $item->saldo_acumulado_previsto = $acumuladoPrevisto;
        }
        // ---------------------------------

        $contas = Conta::all();

        // Passamos também o $saldoInicial para a View
        return view('Mithril::lancamentos.index', compact('listaCombinada', 'contas', 'mes', 'ano', 'contaId', 'saldoInicial'));
    }

    private function diffEmMeses($dataInicio, $dataAtual)
    {
        $inicio = Carbon::parse($dataInicio)->startOfMonth();
        $atual = Carbon::parse($dataAtual)->startOfMonth();
        return $inicio->diffInMonths($atual, false);
    }

    private function getStatus($pt, $dataMesAtual)
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

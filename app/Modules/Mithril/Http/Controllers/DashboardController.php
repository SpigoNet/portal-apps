<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\CartaoFaturaItem;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $hoje = Carbon::now();
        $inicioMes = $hoje->copy()->startOfMonth();
        $fimMes = $hoje->copy()->endOfMonth();

        // Data para buscar o saldo do fechamento anterior
        $mesAnterior = $hoje->copy()->subMonth();

        // 1. Busca todas as contas do usuário
        $contas = Conta::all();

        // 2. Busca os saldos iniciais (do fechamento do mês anterior)
        // Se não houver fechamento, usa o saldo_inicial da conta
        $saldosFechamento = SaldoFechamento::where('ano', $mesAnterior->year)
            ->where('mes', $mesAnterior->month)
            ->pluck('saldo_final', 'conta_id');

        $dadosContas = [];
        $dadosCartoes = [];

        // 3. Carrega transações efetivadas do mês
        $transacoesMes = Transacao::whereBetween('data_efetiva', [$inicioMes, $fimMes])->get();

        // 4. Carrega pré-transações ativas (pendentes)
        // Filtra aquelas que JÁ viraram transação neste mês para não duplicar
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


        // --- PROCESSAMENTO ---

        foreach ($contas as $conta) {
            // Define saldo inicial
            $saldoInicial = $saldosFechamento[$conta->id] ?? $conta->saldo_inicial;

            if ($conta->tipo === 'normal') {
                // Lógica para Contas Correntes
                $realHoje = $saldoInicial;
                $previstoHoje = $saldoInicial;
                $realFimMes = $saldoInicial;
                $previstoFimMes = $saldoInicial;

                // Processa Transações Efetivadas
                foreach ($transacoesMes->where('conta_id', $conta->id) as $t) {
                    $valor = $t->valor;

                    // Impacta Real e Previsto Fim do Mês
                    $realFimMes += $valor;
                    $previstoFimMes += $valor;

                    // Impacta Hoje se já aconteceu
                    if ($t->data_efetiva <= $hoje) {
                        $realHoje += $valor;
                        $previstoHoje += $valor;
                    }
                }

                // Processa Pré-Transações (Pendentes)
                foreach ($preTransacoes->where('conta_id', $conta->id) as $pt) {
                    // Calcula a data de vencimento neste mês
                    $dataVencimento = Carbon::create($hoje->year, $hoje->month, $pt->dia_vencimento);
                    $valor = $pt->valor_parcela;

                    // Pendente só impacta o PREVISTO
                    $previstoFimMes += $valor;

                    if ($dataVencimento <= $hoje) {
                        $previstoHoje += $valor;
                    }
                }

                $dadosContas[] = [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'saldo_inicial' => $saldoInicial,
                    'real_hoje' => $realHoje,
                    'previsto_hoje' => $previstoHoje,
                    'real_fim_mes' => $realFimMes,
                    'previsto_fim_mes' => $previstoFimMes,
                ];

            } elseif ($conta->tipo === 'credito') {
                // Lógica para Cartões de Crédito
                // Soma itens abertos (sem fatura fechada)
                $faturaAberta = CartaoFaturaItem::where('conta_id', $conta->id)
                    ->whereNull('fatura_id')
                    ->sum('valor'); // Geralmente negativo

                // O "Saldo Anterior" num cartão pode ser interpretado como o saldo inicial da conta (divida antiga)
                // ou apenas a fatura atual. Seguindo a lógica do arquivo original:
                $totalPagar = $saldoInicial + $faturaAberta;

                $dadosCartoes[] = [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                    'saldo_anterior' => $saldoInicial,
                    'fatura_aberta' => $faturaAberta,
                    'total_pagar' => $totalPagar
                ];
            }
        }

        return view('Mithril::dashboard.index', compact('dadosContas', 'dadosCartoes'));
    }
}

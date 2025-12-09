<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FechamentoController extends Controller
{
    /**
     * Exibe a lista de contas com sugestões de fechamento
     */
    public function index()
    {
        // Apenas contas normais (Cartão de crédito tem fluxo de fatura separado)
        $contas = Conta::where('tipo', 'normal')->orderBy('nome')->get();

        $dadosFechamento = [];

        foreach ($contas as $conta) {
            // 1. Busca o ÚLTIMO fechamento realizado
            $ultimoFechamento = SaldoFechamento::where('conta_id', $conta->id)
                ->orderBy('ano', 'desc')
                ->orderBy('mes', 'desc')
                ->first();

            // 2. Define o mês que vamos fechar agora (Mês Alvo)
            if ($ultimoFechamento) {
                // Se já tem fechamento, o alvo é o próximo mês
                $dataUltimo = Carbon::create($ultimoFechamento->ano, $ultimoFechamento->mes, 1);
                $dataAlvo = $dataUltimo->copy()->addMonth();
                $saldoInicial = $ultimoFechamento->saldo_final;
            } else {
                // Se nunca fechou, sugerimos o mês passado (ou o atual) e usamos o saldo inicial da conta
                $dataAlvo = now()->startOfMonth(); // Ou subMonth() se preferir fechar o passado
                $saldoInicial = $conta->saldo_inicial;
                $dataUltimo = null;
            }

            // 3. Calcula as movimentações do Mês Alvo
            $transacoesMes = Transacao::where('conta_id', $conta->id)
                ->whereYear('data_efetiva', $dataAlvo->year)
                ->whereMonth('data_efetiva', $dataAlvo->month)
                ->sum('valor');

            // 4. Saldo Sugerido = Saldo Anterior + Movimentações
            $saldoSugerido = $saldoInicial + $transacoesMes;

            $dadosFechamento[] = (object) [
                'conta' => $conta,
                'ultimo_fechamento' => $ultimoFechamento,
                'alvo_mes' => $dataAlvo->month,
                'alvo_ano' => $dataAlvo->year,
                'alvo_data_formatada' => ucfirst($dataAlvo->translatedFormat('F Y')),
                'saldo_sugerido' => $saldoSugerido,
                'movimentacoes' => $transacoesMes
            ];
        }

        return view('Mithril::fechamentos.index', compact('dadosFechamento'));
    }

    /**
     * Processa o fechamento de UMA conta específica
     */
    public function store(Request $request)
    {
        $request->validate([
            'conta_id' => 'required|exists:mithril_contas,id',
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'saldo_final' => 'required|numeric'
        ]);

        // Cria ou Atualiza o fechamento (caso re-faça o mesmo mês)
        SaldoFechamento::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'conta_id' => $request->conta_id,
                'mes' => $request->mes,
                'ano' => $request->ano,
            ],
            [
                'saldo_final' => $request->saldo_final,
                'data_fechamento' => now()
            ]
        );

        return redirect()->route('mithril.fechamentos.index')
            ->with('success', 'Mês fechado com sucesso!');
    }
}

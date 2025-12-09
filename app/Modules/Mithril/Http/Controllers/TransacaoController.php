<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Transacao;
use App\Modules\Mithril\Models\Conta;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransacaoController extends Controller
{
    /**
     * Exibe a lista de lançamentos do mês (equivalente a lancamentos.php).
     */
    public function index(Request $request)
    {
        // Filtros de Data
        $mes = $request->input('mes', date('m'));
        $ano = $request->input('ano', date('Y'));
        $contaId = $request->input('conta_id');

        // Título formatado
        $dataTitulo = Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM [de] YYYY');

        // Query de Transações
        $query = Transacao::with('conta')
            ->whereMonth('data_efetiva', $mes)
            ->whereYear('data_efetiva', $ano)
            ->orderBy('data_efetiva', 'asc');

        if ($contaId) {
            $query->where('conta_id', $contaId);
        }

        $transacoes = $query->get();

        // Buscar Contas para o filtro
        $contas = Conta::orderBy('nome')->get();

        // Cálculo de Saldos (Lógica simplificada do procedural para o Eloquent)
        // Nota: Em um sistema real, o cálculo de saldo acumulado idealmente viria de um Service
        // ou seria calculado na View ou via SQL Window Functions.
        // Aqui mantemos simples para seguir o padrão MVC.

        $totalEntradas = $transacoes->where('valor', '>=', 0)->sum('valor');
        $totalSaidas = $transacoes->where('valor', '<', 0)->sum('valor');
        $saldoMes = $totalEntradas + $totalSaidas;

        return view('Mithril::index', compact(
            'transacoes',
            'contas',
            'mes',
            'ano',
            'dataTitulo',
            'saldoMes',
            'contaId'
        ));
    }

    /**
     * Exibe o formulário de criação (equivalente a transacao_form.php).
     */
    public function create()
    {
        $contas = Conta::orderBy('nome')->get();
        return view('Mithril::create', compact('contas'));
    }

    /**
     * Salva a transação (equivalente a transacao_insert.php).
     */
    public function store(Request $request)
    {
        // 1. Validação
        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required', // Tratamento de decimal será feito abaixo
            'data_efetiva' => 'required|date',
            'conta_id' => 'required|exists:contas,id',
            'operacao' => 'required|in:debito,credito',
        ]);

        // 2. Tratamento do Valor (lógica para converter R$ 1.000,00 ou string para float)
        $valor = $request->input('valor');
        if (is_string($valor)) {
            $valor = (float) str_replace(',', '.', preg_replace('/[^\d,\.\-]/', '', $valor));
        }

        // Aplica lógica de Débito/Crédito
        if ($request->input('operacao') === 'debito') {
            $valor = -abs($valor);
        } else {
            $valor = abs($valor);
        }

        // 3. Criação
        Transacao::create([
            'descricao' => $validated['descricao'],
            'valor' => $valor,
            'data_efetiva' => $validated['data_efetiva'],
            'conta_id' => $validated['conta_id'],
        ]);

        // 4. Redirecionamento
        return redirect()
            ->route('mithril.lancamentos.index')
            ->with('success', 'Transação inserida com sucesso.');
    }
}

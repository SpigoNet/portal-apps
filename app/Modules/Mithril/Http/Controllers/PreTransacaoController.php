<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use Illuminate\Http\Request;

class PreTransacaoController extends Controller
{
    /**
     * Lista todas as pré-transações (Ativas e Inativas)
     */
    public function index()
    {
        $preTransacoes = PreTransacao::with('conta')
            ->orderBy('ativa', 'desc') // Ativas primeiro
            ->orderBy('dia_vencimento') // Depois por dia
            ->get();

        return view('Mithril::pre_transacoes.index', compact('preTransacoes'));
    }

    /**
     * Exibe formulário de criação
     */
    public function create()
    {
        $contas = Conta::where('tipo', 'normal')->orderBy('nome')->get();
        return view('Mithril::pre_transacoes.create', compact('contas'));
    }

    /**
     * Salva nova pré-transação
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        $dados = $request->all();
        $dados['user_id'] = auth()->id();

        // Ajusta o sinal do valor (Débito vira negativo)
        $valorLimpo = $this->limparValor($request->valor_parcela);
        $dados['valor_parcela'] = $request->operacao === 'debito' ? -abs($valorLimpo) : abs($valorLimpo);

        // Define campos nulos se for recorrente
        if ($request->tipo === 'recorrente') {
            $dados['total_parcelas'] = null;
            $dados['parcela_atual'] = 0;
        }

        PreTransacao::create($dados);

        return redirect()->route('mithril.pre-transacoes.index')
            ->with('success', 'Pré-transação criada com sucesso.');
    }

    /**
     * Exibe formulário de edição
     */
    public function edit($id)
    {
        $preTransacao = PreTransacao::findOrFail($id);
        $contas = Conta::where('tipo', 'normal')->orderBy('nome')->get();

        return view('Mithril::pre_transacoes.edit', compact('preTransacao', 'contas'));
    }

    /**
     * Atualiza pré-transação existente
     */
    public function update(Request $request, $id)
    {
        $this->validateRequest($request);
        $preTransacao = PreTransacao::findOrFail($id);

        $dados = $request->all();

        // Ajusta o sinal do valor
        $valorLimpo = $this->limparValor($request->valor_parcela);
        $dados['valor_parcela'] = $request->operacao === 'debito' ? -abs($valorLimpo) : abs($valorLimpo);

        if ($request->tipo === 'recorrente') {
            $dados['total_parcelas'] = null;
            $dados['parcela_atual'] = 0;
        }

        $preTransacao->update($dados);

        return redirect()->route('mithril.pre-transacoes.index')
            ->with('success', 'Pré-transação atualizada com sucesso.');
    }

    /**
     * Remove (Exclusão definitiva)
     */
    public function destroy($id)
    {
        $preTransacao = PreTransacao::findOrFail($id);
        $preTransacao->delete();

        return redirect()->route('mithril.pre-transacoes.index')
            ->with('success', 'Pré-transação removida.');
    }

    /**
     * Alterna status Ativo/Inativo (Substituto do toggle_status.php)
     */
    public function toggleStatus($id)
    {
        $pt = PreTransacao::findOrFail($id);
        $pt->ativa = !$pt->ativa;
        $pt->save();

        return redirect()->back()->with('success', 'Status alterado.');
    }

    // --- Auxiliares ---

    private function validateRequest(Request $request)
    {
        $request->validate([
            'descricao' => 'required|string|max:255',
            'valor_parcela' => 'required', // Pode vir como string formatada
            'conta_id' => 'required|exists:mithril_contas,id',
            'dia_vencimento' => 'required|integer|min:1|max:31',
            'tipo' => 'required|in:recorrente,parcelada',
            'operacao' => 'required|in:debito,credito',
            // Validações condicionais para parcelamento
            'total_parcelas' => 'required_if:tipo,parcelada|nullable|integer|min:1',
            'data_inicio' => 'required_if:tipo,parcelada|nullable|date',
        ]);
    }

    private function limparValor($valor)
    {
        // Converte "1.234,56" para "1234.56" se necessário, ou mantém float
        if (is_string($valor)) {
            $valor = str_replace('.', '', $valor); // Remove milhar
            $valor = str_replace(',', '.', $valor); // Troca vírgula por ponto
        }
        return (float) $valor;
    }
}

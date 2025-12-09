<?php

namespace App\Modules\Mithril\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PreTransacaoAcoesController extends Controller
{
    /**
     * Passo 1: Formulário de Confirmação
     */
    public function showConfirmForm(Request $request, $id)
    {
        $preTransacao = PreTransacao::with('conta')->findOrFail($id);

        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        // CORREÇÃO: Capturamos o conta_id da URL
        $contaId = $request->input('conta_id');

        $dataSugerida = Carbon::create($ano, $mes, $preTransacao->dia_vencimento);

        // CORREÇÃO: Passamos o contaId para a view
        return view('Mithril::pre_transacoes.acoes.confirmar', compact('preTransacao', 'dataSugerida', 'mes', 'ano', 'contaId'));
    }

    /**
     * Passo 1 (Ação): Salvar Confirmação
     */
    public function confirmar(Request $request, $id)
    {
        $request->validate([
            'valor' => 'required|numeric',
            'data_vencimento' => 'required|date',
        ]);

        $pt = PreTransacao::findOrFail($id);

        $novaData = Carbon::parse($request->data_vencimento);

        // Atualiza a pré-transação
        $pt->valor_parcela = $request->valor;
        $pt->dia_vencimento = $novaData->day;
        $pt->data_ultima_acao = $novaData->format('Y-m-d');
        $pt->save();

        // CORREÇÃO: Redirecionamento explícito mantendo TODOS os filtros
        return redirect()->route('mithril.lancamentos.index', [
            'mes' => $request->mes,
            'ano' => $request->ano,
            'conta_id' => $request->conta_id // <--- O segredo está aqui
        ])->with('success', 'Fatura confirmada e valor atualizado.');
    }

    /**
     * Passo 2: Efetivar (O Pagamento Real)
     */
    public function efetivar(Request $request, $id)
    {
        $pt = PreTransacao::findOrFail($id);

        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        Transacao::create([
            'user_id' => auth()->id(),
            'conta_id' => $pt->conta_id,
            'pre_transacao_id' => $pt->id,
            'descricao' => $pt->descricao,
            'valor' => $pt->valor_parcela,
            'data_efetiva' => Carbon::create($ano, $mes, $pt->dia_vencimento)->format('Y-m-d'),
        ]);

        if ($pt->tipo === 'parcelada') {
            $pt->increment('parcela_atual');
        }

        // CORREÇÃO: Em vez de back(), forçamos o redirecionamento com filtros
        // Isso previne que um "refresh" na página reenvie o formulário e garante os filtros
        return redirect()->route('mithril.lancamentos.index', [
            'mes' => $mes,
            'ano' => $ano,
            'conta_id' => $request->input('conta_id') // <--- Mantém o filtro
        ])->with('success', 'Pagamento registado com sucesso!');
    }
}

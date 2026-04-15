<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Transacao;
use App\Modules\Mithril\Models\Conta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TransacaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        $contaId = $request->input('conta_id');

        $query = Transacao::with('conta')
            ->whereMonth('data_efetiva', $mes)
            ->whereYear('data_efetiva', $ano)
            ->orderBy('data_efetiva', 'asc');

        if ($contaId) {
            $query->where('conta_id', $contaId);
        }

        $transacoes = $query->get();

        $totalEntradas = $transacoes->where('valor', '>=', 0)->sum('valor');
        $totalSaidas = $transacoes->where('valor', '<', 0)->sum('valor');

        return response()->json([
            'data' => $transacoes->map(fn ($t) => [
                'id' => $t->id,
                'descricao' => $t->descricao,
                'valor' => (float) $t->valor,
                'data_efetiva' => $t->data_efetiva?->format('Y-m-d'),
                'conta' => $t->conta ? [
                    'id' => $t->conta->id,
                    'nome' => $t->conta->nome,
                ] : null,
                'pre_transacao_id' => $t->pre_transacao_id,
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'mes' => (int) $mes,
                'ano' => (int) $ano,
                'total_entradas' => (float) $totalEntradas,
                'total_saidas' => (float) $totalSaidas,
                'saldo_mes' => (float) ($totalEntradas + $totalSaidas),
                'total_registros' => $transacoes->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric',
            'data_efetiva' => 'required|date',
            'conta_id' => 'required|exists:mithril_contas,id',
            'operacao' => 'required|in:debito,credito',
        ]);

        $valor = (float) $validated['valor'];
        if ($validated['operacao'] === 'debito') {
            $valor = -abs($valor);
        } else {
            $valor = abs($valor);
        }

        $transacao = Transacao::create([
            'descricao' => $validated['descricao'],
            'valor' => $valor,
            'data_efetiva' => $validated['data_efetiva'],
            'conta_id' => $validated['conta_id'],
        ]);

        return response()->json([
            'data' => [
                'id' => $transacao->id,
                'descricao' => $transacao->descricao,
                'valor' => (float) $transacao->valor,
                'data_efetiva' => $transacao->data_efetiva?->format('Y-m-d'),
            ],
            'message' => 'Transação criada com sucesso.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $transacao = Transacao::with('conta')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $transacao->id,
                'descricao' => $transacao->descricao,
                'valor' => (float) $transacao->valor,
                'data_efetiva' => $transacao->data_efetiva?->format('Y-m-d'),
                'conta' => $transacao->conta ? [
                    'id' => $transacao->conta->id,
                    'nome' => $transacao->conta->nome,
                ] : null,
                'pre_transacao_id' => $transacao->pre_transacao_id,
                'created_at' => $transacao->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $transacao = Transacao::findOrFail($id);
        $transacao->delete();

        return response()->json([
            'message' => 'Transação removida com sucesso.',
        ]);
    }
}
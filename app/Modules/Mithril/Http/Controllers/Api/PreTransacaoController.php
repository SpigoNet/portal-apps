<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\Transacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PreTransacaoController extends Controller
{
    public function index(): JsonResponse
    {
        $preTransacoes = PreTransacao::with('conta')
            ->orderBy('ativa', 'desc')
            ->orderBy('dia_vencimento')
            ->get();

        return response()->json([
            'data' => $preTransacoes->map(fn ($pt) => $this->transform($pt)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor_parcela' => 'required|numeric',
            'conta_id' => 'required|exists:mithril_contas,id',
            'dia_vencimento' => 'required|integer|min:1|max:31',
            'tipo' => 'required|in:recorrente,parcelada',
            'operacao' => 'required|in:debito,credito',
            'total_parcelas' => 'required_if:tipo,parcelada|nullable|integer|min:1',
            'data_inicio' => 'required_if:tipo,parcelada|nullable|date',
        ]);

        $valor = (float) $validated['valor_parcela'];
        if ($validated['operacao'] === 'debito') {
            $valor = -abs($valor);
        } else {
            $valor = abs($valor);
        }

        $dados = [
            'user_id' => auth()->id(),
            'descricao' => $validated['descricao'],
            'valor_parcela' => $valor,
            'conta_id' => $validated['conta_id'],
            'dia_vencimento' => $validated['dia_vencimento'],
            'tipo' => $validated['tipo'],
            'ativa' => true,
        ];

        if ($validated['tipo'] === 'recorrente') {
            $dados['total_parcelas'] = null;
            $dados['parcela_atual'] = 0;
        } else {
            $dados['total_parcelas'] = $validated['total_parcelas'];
            $dados['parcela_atual'] = 0;
            $dados['data_inicio'] = $validated['data_inicio'];
        }

        $preTransacao = PreTransacao::create($dados);

        return response()->json([
            'data' => $this->transform($preTransacao->fresh('conta')),
            'message' => 'Pré-transação criada com sucesso.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $preTransacao = PreTransacao::with('conta')->findOrFail($id);

        return response()->json([
            'data' => $this->transform($preTransacao),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $preTransacao = PreTransacao::findOrFail($id);

        $validated = $request->validate([
            'descricao' => 'sometimes|string|max:255',
            'valor_parcela' => 'sometimes|numeric',
            'conta_id' => 'sometimes|exists:mithril_contas,id',
            'dia_vencimento' => 'sometimes|integer|min:1|max:31',
            'tipo' => 'sometimes|in:recorrente,parcelada',
            'operacao' => 'sometimes|in:debito,credito',
            'total_parcelas' => 'sometimes|integer|min:1',
            'data_inicio' => 'sometimes|date',
        ]);

        if (isset($validated['valor_parcela'])) {
            $valor = (float) $validated['valor_parcela'];
            $operacao = $validated['operacao'] ?? ($valor < 0 ? 'debito' : 'credito');
            if ($operacao === 'debito') {
                $valor = -abs($valor);
            } else {
                $valor = abs($valor);
            }
            $validated['valor_parcela'] = $valor;
        }

        if (isset($validated['tipo']) && $validated['tipo'] === 'recorrente') {
            $validated['total_parcelas'] = null;
            $validated['parcela_atual'] = 0;
        }

        $preTransacao->update($validated);

        return response()->json([
            'data' => $this->transform($preTransacao->fresh('conta')),
            'message' => 'Pré-transação atualizada com sucesso.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $preTransacao = PreTransacao::findOrFail($id);
        $preTransacao->delete();

        return response()->json([
            'message' => 'Pré-transação removida com sucesso.',
        ]);
    }

    public function toggle(int $id): JsonResponse
    {
        $pt = PreTransacao::findOrFail($id);
        $pt->ativa = !$pt->ativa;
        $pt->save();

        return response()->json([
            'data' => $this->transform($pt->fresh('conta')),
            'message' => $pt->ativa ? 'Pré-transação ativada.' : 'Pré-transação desativada.',
        ]);
    }

    public function efetivar(Request $request, int $id): JsonResponse
    {
        $pt = PreTransacao::findOrFail($id);

        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $transacao = Transacao::create([
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

        return response()->json([
            'data' => [
                'id' => $transacao->id,
                'descricao' => $transacao->descricao,
                'valor' => (float) $transacao->valor,
                'data_efetiva' => $transacao->data_efetiva?->format('Y-m-d'),
            ],
            'message' => 'Parcela efetivada com sucesso.',
        ], 201);
    }

    private function transform($pt): array
    {
        return [
            'id' => $pt->id,
            'descricao' => $pt->descricao,
            'valor_parcela' => (float) $pt->valor_parcela,
            'conta' => $pt->conta ? [
                'id' => $pt->conta->id,
                'nome' => $pt->conta->nome,
            ] : null,
            'dia_vencimento' => $pt->dia_vencimento,
            'tipo' => $pt->tipo,
            'total_parcelas' => $pt->total_parcelas,
            'parcela_atual' => $pt->parcela_atual,
            'ativa' => $pt->ativa,
            'data_inicio' => $pt->data_inicio?->format('Y-m-d'),
            'data_ultima_acao' => $pt->data_ultima_acao?->format('Y-m-d'),
            'created_at' => $pt->created_at?->toIso8601String(),
        ];
    }
}
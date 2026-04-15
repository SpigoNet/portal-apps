<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContaController extends Controller
{
    public function index(): JsonResponse
    {
        $contas = Conta::all();

        return response()->json([
            'data' => $contas->map(fn ($conta) => [
                'id' => $conta->id,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'saldo_inicial' => (float) $conta->saldo_inicial,
                'dia_fechamento' => $conta->dia_fechamento,
                'dia_vencimento' => $conta->dia_vencimento,
                'conta_debito_id' => $conta->conta_debito_id,
                'created_at' => $conta->created_at?->toIso8601String(),
                'updated_at' => $conta->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:normal,credito',
            'saldo_inicial' => 'nullable|numeric',
            'dia_fechamento' => 'nullable|integer|min:1|max:31',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
            'conta_debito_id' => 'nullable|exists:mithril_contas,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['saldo_inicial'] = $validated['saldo_inicial'] ?? 0;

        $conta = Conta::create($validated);

        return response()->json([
            'data' => [
                'id' => $conta->id,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'saldo_inicial' => (float) $conta->saldo_inicial,
                'dia_fechamento' => $conta->dia_fechamento,
                'dia_vencimento' => $conta->dia_vencimento,
                'created_at' => $conta->created_at?->toIso8601String(),
            ],
            'message' => 'Conta criada com sucesso.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $conta = Conta::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $conta->id,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'saldo_inicial' => (float) $conta->saldo_inicial,
                'dia_fechamento' => $conta->dia_fechamento,
                'dia_vencimento' => $conta->dia_vencimento,
                'conta_debito_id' => $conta->conta_debito_id,
                'created_at' => $conta->created_at?->toIso8601String(),
                'updated_at' => $conta->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $conta = Conta::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:normal,credito',
            'saldo_inicial' => 'nullable|numeric',
            'dia_fechamento' => 'nullable|integer|min:1|max:31',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
            'conta_debito_id' => 'nullable|exists:mithril_contas,id',
        ]);

        $conta->update($validated);

        return response()->json([
            'data' => [
                'id' => $conta->id,
                'nome' => $conta->nome,
                'tipo' => $conta->tipo,
                'saldo_inicial' => (float) $conta->saldo_inicial,
                'updated_at' => $conta->updated_at?->toIso8601String(),
            ],
            'message' => 'Conta atualizada com sucesso.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $conta = Conta::findOrFail($id);
        $conta->delete();

        return response()->json([
            'message' => 'Conta removida com sucesso.',
        ]);
    }
}
<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FechamentoController extends Controller
{
    public function index(): JsonResponse
    {
        $contas = Conta::where('tipo', 'normal')->orderBy('nome')->get();
        $dadosFechamento = [];

        foreach ($contas as $conta) {
            $ultimoFechamento = SaldoFechamento::where('conta_id', $conta->id)
                ->orderBy('ano', 'desc')
                ->orderBy('mes', 'desc')
                ->first();

            if ($ultimoFechamento) {
                $dataUltimo = Carbon::create($ultimoFechamento->ano, $ultimoFechamento->mes, 1);
                $dataAlvo = $dataUltimo->copy()->addMonth();
                $saldoInicial = $ultimoFechamento->saldo_final;
            } else {
                $dataAlvo = now()->startOfMonth();
                $saldoInicial = $conta->saldo_inicial;
                $ultimoFechamento = null;
            }

            $transacoesMes = Transacao::where('conta_id', $conta->id)
                ->whereYear('data_efetiva', $dataAlvo->year)
                ->whereMonth('data_efetiva', $dataAlvo->month)
                ->sum('valor');

            $saldoSugerido = $saldoInicial + $transacoesMes;

            $dadosFechamento[] = [
                'conta' => [
                    'id' => $conta->id,
                    'nome' => $conta->nome,
                ],
                'ultimo_fechamento' => $ultimoFechamento ? [
                    'mes' => $ultimoFechamento->mes,
                    'ano' => $ultimoFechamento->ano,
                    'saldo_final' => (float) $ultimoFechamento->saldo_final,
                ] : null,
                'alvo_mes' => $dataAlvo->month,
                'alvo_ano' => $dataAlvo->year,
                'alvo_data_formatada' => ucfirst($dataAlvo->translatedFormat('F Y')),
                'saldo_inicial' => (float) $saldoInicial,
                'movimentacoes' => (float) $transacoesMes,
                'saldo_sugerido' => (float) $saldoSugerido,
            ];
        }

        return response()->json([
            'data' => $dadosFechamento,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conta_id' => 'required|exists:mithril_contas,id',
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'saldo_final' => 'required|numeric',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['data_fechamento'] = now();

        $fechamento = SaldoFechamento::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'conta_id' => $validated['conta_id'],
                'mes' => $validated['mes'],
                'ano' => $validated['ano'],
            ],
            [
                'saldo_final' => $validated['saldo_final'],
                'data_fechamento' => $validated['data_fechamento'],
            ]
        );

        return response()->json([
            'data' => [
                'id' => $fechamento->id,
                'conta_id' => $fechamento->conta_id,
                'mes' => $fechamento->mes,
                'ano' => $fechamento->ano,
                'saldo_final' => (float) $fechamento->saldo_final,
                'data_fechamento' => $fechamento->data_fechamento?->format('Y-m-d'),
            ],
            'message' => 'Fechamento registrado com sucesso.',
        ], 201);
    }
}
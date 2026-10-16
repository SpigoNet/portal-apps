<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\HistoricoRefeicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistoricoController extends Controller
{
    public function index(Request $request): View
    {
        $casa = $this->casa($request);
        $historicos = $casa->historicos()->with('receita')->orderByDesc('consumida_em')->get();
        $ingredientes = $casa->ingredientes()->get()->keyBy('id');

        // Frequência real por receita: quantas vezes, última vez e quanto gostaram.
        $receitas = $historicos->whereNotNull('receita_id')->groupBy('receita_id')->map(function ($feitas) {
            $valores = $feitas->map(fn (HistoricoRefeicao $h) => $h->valor())->filter(fn ($v) => $v !== null);

            return [
                'receita' => $feitas->first()->receita,
                'vezes' => $feitas->count(),
                'ultima' => $feitas->max('data'),
                'media' => $valores->isEmpty() ? null : $valores->avg(),
                'rejeitada' => $feitas->first(fn ($h) => $h->feedback !== null)?->feedback === 'nao_fazer_novamente',
            ];
        })->sortByDesc('vezes')->values();

        return view('Cardapio::historico.index', [
            'historicos' => $historicos->take(60),
            'receitas' => $receitas,
            'rejeitadas' => $receitas->where('rejeitada', true),
            'ingredientes' => $ingredientes,
        ]);
    }

    public function feedback(Request $request, HistoricoRefeicao $historico): RedirectResponse
    {
        abort_unless($historico->casa_id === $this->casa($request)->id, 404);

        $dados = $request->validate([
            'feedback' => ['nullable', Rule::in(array_keys(HistoricoRefeicao::FEEDBACKS))],
            'nota' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $historico->update(['feedback' => $dados['feedback'] ?? null, 'nota' => $dados['nota'] ?? null]);

        return back()->with('status', 'Feedback salvo. Ele passa a pesar nas próximas sugestões.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }
}

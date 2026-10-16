<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Support\TipoRefeicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RotinaController extends Controller
{
    public function index(Request $request): View
    {
        $casa = $this->casa($request);

        // Marcadas: "tipo|pessoa|dia" => true
        $marcadas = $casa->regrasRotina()->where('ativo', true)->get()
            ->mapWithKeys(fn ($r) => ["{$r->tipo_refeicao}|{$r->pessoa_id}|{$r->dia_semana}" => true])
            ->all();

        return view('Cardapio::rotina.index', [
            'casa' => $casa,
            'pessoas' => $casa->pessoas()->where('ativo', true)->orderBy('nome')->get(),
            'marcadas' => $marcadas,
        ]);
    }

    public function salvarRefeicoes(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'refeicoes' => ['required', 'array', 'min:1'],
            'refeicoes.*' => ['string', Rule::in(array_keys(TipoRefeicao::rotulos()))],
            'lancheira_itens' => ['nullable', 'integer', 'between:1,6'],
        ]);

        $this->casa($request)->update([
            'refeicoes_ativas' => array_values($dados['refeicoes']),
            'lancheira_itens' => $dados['lancheira_itens'] ?? 3,
        ]);

        return redirect()->route('cardapio.rotina.index')->with('status', 'Refeições atualizadas.');
    }

    public function salvar(Request $request): RedirectResponse
    {
        $casa = $this->casa($request);
        $pessoasIds = $casa->pessoas()->pluck('id')->all();
        $tipos = $casa->refeicoes();

        $dados = $request->validate([
            'rotina' => ['nullable', 'array'],
            'rotina.*' => ['array'],
            'rotina.*.*' => ['array'],
            'rotina.*.*.*' => ['integer', 'between:1,7'],
        ]);

        DB::transaction(function () use ($casa, $dados, $pessoasIds, $tipos): void {
            $casa->regrasRotina()->delete();

            foreach ($dados['rotina'] ?? [] as $tipo => $porPessoa) {
                if (! in_array($tipo, $tipos, true)) {
                    continue;
                }

                foreach ($porPessoa as $pessoaId => $dias) {
                    if (! in_array((int) $pessoaId, $pessoasIds, true)) {
                        continue;
                    }

                    foreach (array_unique($dias) as $dia) {
                        $casa->regrasRotina()->create([
                            'pessoa_id' => (int) $pessoaId,
                            'dia_semana' => (int) $dia,
                            'tipo_refeicao' => $tipo,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('cardapio.rotina.index')->with('status', 'Rotina salva.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }
}

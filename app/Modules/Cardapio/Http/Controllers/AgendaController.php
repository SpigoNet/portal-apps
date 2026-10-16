<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Ausencia;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Evento;
use App\Modules\Cardapio\Services\AgendaService;
use App\Modules\Cardapio\Support\TipoRefeicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Eventos (refeição fora, convidados) e ausências de pessoas; ao mudar, as semanas já planejadas são reajustadas.
 */
class AgendaController extends Controller
{
    public function __construct(private readonly AgendaService $agenda) {}

    public function index(Request $request): View
    {
        $casa = $this->casa($request);
        $hoje = today();

        return view('Cardapio::agenda.index', [
            'eventos' => $casa->eventos()->whereDate('data', '>=', $hoje->copy()->subDays(7))->orderBy('data')->get(),
            'ausencias' => $casa->ausencias()->with('pessoa')->whereDate('fim', '>=', $hoje)->orderBy('inicio')->get(),
            'pessoas' => $casa->pessoas()->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function salvarEvento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'tipo_refeicao' => ['nullable', Rule::in(array_keys(TipoRefeicao::rotulos()))],
            'tipo' => ['required', Rule::in(array_keys(Evento::TIPOS))],
            'titulo' => ['required', 'string', 'max:160'],
            'pessoas_extras' => ['nullable', 'integer', 'between:0,200'],
        ]);

        if ($dados['tipo'] === 'convidados' && (int) ($dados['pessoas_extras'] ?? 0) < 1) {
            return back()->withErrors(['pessoas_extras' => 'Informe quantas pessoas a mais virão.'])->withInput();
        }

        $this->agenda->criarEvento($this->casa($request), $dados);

        return redirect()->route('cardapio.agenda.index')->with('status', 'Evento salvo e semanas planejadas ajustadas.');
    }

    public function removerEvento(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless($evento->casa_id === $this->casa($request)->id, 404);
        $this->agenda->removerEvento($evento);

        return back()->with('status', 'Evento removido. Use “Sincronizar com a rotina” na semana para recolocar as refeições.');
    }

    public function salvarAusencia(Request $request): RedirectResponse
    {
        $casa = $this->casa($request);
        $dados = $request->validate([
            'pessoa_id' => ['required', 'integer'],
            'inicio' => ['required', 'date'],
            'fim' => ['required', 'date', 'after_or_equal:inicio'],
            'motivo' => ['nullable', 'string', 'max:160'],
        ]);

        $pessoa = $casa->pessoas()->findOrFail($dados['pessoa_id']);
        $this->agenda->criarAusencia($casa, $pessoa, $dados);

        return redirect()->route('cardapio.agenda.index')->with('status', "{$pessoa->nome} fora do cálculo das refeições no período.");
    }

    public function removerAusencia(Request $request, Ausencia $ausencia): RedirectResponse
    {
        abort_unless($ausencia->casa_id === $this->casa($request)->id, 404);
        $this->agenda->removerAusencia($ausencia);

        return back()->with('status', 'Ausência removida. Use “Sincronizar com a rotina” na semana para recolocar a pessoa.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }
}

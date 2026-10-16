<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Services\Assistente\AssistenteService;
use App\Modules\Cardapio\Services\Assistente\IaIndisponivelException;
use App\Modules\Cardapio\Services\Assistente\InterpretadorComandos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assistente em linguagem natural. A proposta (ações já validadas) fica na sessão do usuário e só o que
 * está nela pode ser aplicado: o navegador manda apenas o token e os ids marcados, nunca os dados da ação.
 */
class AssistenteController extends Controller
{
    private const SESSAO = 'cardapio.assistente';

    public function __construct(private readonly AssistenteService $assistente) {}

    public function index(Request $request): View
    {
        return view('Cardapio::assistente.index', [
            'proposta' => $request->session()->get(self::SESSAO),
            'resultados' => $request->session()->get('assistente_resultados'),
            'exemplos' => [
                'Comprei 2 kg de frango, 1 dúzia de ovos e 5 bananas',
                'Acabou o arroz',
                'Vou receber meus pais domingo, seremos seis pessoas',
                'Meu filho não gostou da banana, não coloque mais',
                'O que posso fazer para o jantar hoje?',
                'Tenho frango, batata e tomate. O que dá para fazer?',
                'Monte o cardápio da semana',
                'Crie uma receita de panqueca simples',
            ],
        ]);
    }

    public function interpretar(Request $request): RedirectResponse
    {
        $dados = $request->validate(['texto' => ['required', 'string', 'max:'.InterpretadorComandos::MAX_TEXTO]]);

        try {
            $proposta = $this->assistente->interpretar($this->casa($request), $this->falante($request), $request->user(), $dados['texto']);
        } catch (IaIndisponivelException $e) {
            return back()->withErrors(['texto' => $e->getMessage()])->withInput();
        }

        $request->session()->put(self::SESSAO, ['token' => Str::random(32), 'texto' => $dados['texto']] + $proposta);

        return redirect()->route('cardapio.assistente.index');
    }

    public function aplicar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string'],
            'acoes' => ['required', 'array', 'min:1'],
            'acoes.*' => ['string', 'max:10'],
        ], ['acoes.required' => 'Marque pelo menos uma ação para aplicar.']);

        $proposta = $request->session()->get(self::SESSAO);

        if (! is_array($proposta) || ! hash_equals((string) $proposta['token'], $dados['token'])) {
            return redirect()->route('cardapio.assistente.index')->withErrors(['texto' => 'Esta proposta expirou. Peça de novo.']);
        }

        // Aplicar é uma vez só: a proposta sai da sessão antes de executar.
        $request->session()->forget(self::SESSAO);

        $resultados = $this->assistente->aplicar($this->casa($request), $this->falante($request), $proposta['pendentes'], $dados['acoes']);

        return redirect()->route('cardapio.assistente.index')->with('assistente_resultados', $resultados);
    }

    public function descartar(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSAO);

        return redirect()->route('cardapio.assistente.index');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function falante(Request $request): Pessoa
    {
        return $request->attributes->get('pessoaAtual');
    }
}

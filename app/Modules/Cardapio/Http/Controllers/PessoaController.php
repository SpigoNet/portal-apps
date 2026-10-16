<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PessoaController extends Controller
{
    public function index(Request $request): View
    {
        return view('Cardapio::pessoas.index', [
            'casa' => $this->casa($request),
            'pessoas' => $this->casa($request)->pessoas()->with('usuario')->orderBy('nome')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->exigirAdulto($request);

        return view('Cardapio::pessoas.form', [
            'pessoa' => new Pessoa(['tipo' => 'adulto', 'ativo' => true]),
            'usuarios' => $this->usuariosLivres(null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->exigirAdulto($request);

        $this->casa($request)->pessoas()->create($this->validar($request, null));

        return redirect()->route('cardapio.pessoas.index')->with('status', 'Pessoa cadastrada.');
    }

    public function edit(Request $request, Pessoa $pessoa): View
    {
        $this->autorizar($request, $pessoa);

        return view('Cardapio::pessoas.form', [
            'pessoa' => $pessoa,
            'usuarios' => $this->usuariosLivres($pessoa),
        ]);
    }

    public function update(Request $request, Pessoa $pessoa): RedirectResponse
    {
        $this->autorizar($request, $pessoa);

        $dados = $this->validar($request, $pessoa);

        // Quem tem login não pode se desativar nem se desvincular, senão perderia o acesso à própria casa.
        if ($pessoa->user_id === $request->user()->id) {
            $dados['user_id'] = $pessoa->user_id;
            $dados['ativo'] = true;
        }

        $pessoa->update($dados);

        return redirect()->route('cardapio.pessoas.index')->with('status', 'Pessoa atualizada.');
    }

    public function destroy(Request $request, Pessoa $pessoa): RedirectResponse
    {
        $this->autorizar($request, $pessoa);

        if ($pessoa->user_id === $request->user()->id) {
            return back()->withErrors(['pessoa' => 'Você não pode remover a si mesmo da casa.']);
        }

        $pessoa->delete();

        return redirect()->route('cardapio.pessoas.index')->with('status', 'Pessoa removida.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, Pessoa $pessoa): void
    {
        abort_unless($pessoa->casa_id === $this->casa($request)->id, 404);
        $this->exigirAdulto($request);
    }

    /**
     * Quem tem login e é adulto gerencia a casa; criança com login só vê.
     */
    private function exigirAdulto(Request $request): void
    {
        abort_unless($request->attributes->get('pessoaAtual')->tipo === 'adulto', 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Pessoa $pessoa): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::in(['adulto', 'crianca'])],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('cardapio_pessoas', 'user_id')->ignore($pessoa?->id)],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);

        $dados['ativo'] = $request->boolean('ativo', true);
        $dados['user_id'] = $dados['user_id'] ?? null;

        return $dados;
    }

    /**
     * Usuários ainda não vinculados a nenhuma pessoa (mais o já vinculado à pessoa editada).
     */
    private function usuariosLivres(?Pessoa $pessoa)
    {
        return User::query()
            ->whereNotIn('id', Pessoa::whereNotNull('user_id')->when($pessoa, fn ($q) => $q->where('id', '!=', $pessoa->id))->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}

<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Models\Preferencia;
use App\Modules\Cardapio\Models\Restricao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Restrições e preferências alimentares de uma pessoa.
 * Adultos com login editam qualquer pessoa da casa; os demais, só o próprio perfil.
 */
class PerfilController extends Controller
{
    public function show(Request $request, Pessoa $pessoa): View
    {
        $this->autorizar($request, $pessoa);

        return view('Cardapio::perfil.show', [
            'pessoa' => $pessoa,
            'restricoes' => $pessoa->restricoes()->with('ingrediente')->get(),
            'preferencias' => $pessoa->preferencias()->with('ingrediente')->orderByDesc('nivel')->get(),
            'ingredientes' => $this->casa($request)->ingredientes()->orderBy('nome')->get(),
        ]);
    }

    public function salvarRestricao(Request $request, Pessoa $pessoa): RedirectResponse
    {
        $this->autorizar($request, $pessoa);

        $dados = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(Restricao::TIPOS))],
            'ingrediente_id' => ['nullable', 'integer'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);

        $ingredienteId = $this->ingredienteDaCasa($request, $dados['ingrediente_id'] ?? null);

        if ($ingredienteId === null && empty($dados['descricao'])) {
            return back()->withErrors(['ingrediente_id' => 'Escolha um ingrediente ou descreva o objetivo.'])->withInput();
        }

        $pessoa->restricoes()->create([
            'tipo' => $dados['tipo'],
            'ingrediente_id' => $ingredienteId,
            'descricao' => $dados['descricao'] ?? null,
        ]);

        return back()->with('status', 'Restrição adicionada.');
    }

    public function removerRestricao(Request $request, Pessoa $pessoa, Restricao $restricao): RedirectResponse
    {
        $this->autorizar($request, $pessoa);
        abort_unless($restricao->pessoa_id === $pessoa->id, 404);
        $restricao->delete();

        return back()->with('status', 'Restrição removida.');
    }

    public function salvarPreferencia(Request $request, Pessoa $pessoa): RedirectResponse
    {
        $this->autorizar($request, $pessoa);

        $dados = $request->validate([
            'ingrediente_id' => ['required', 'integer'],
            'nivel' => ['required', 'integer', Rule::in(array_keys(Preferencia::NIVEIS))],
        ]);

        $ingredienteId = $this->ingredienteDaCasa($request, $dados['ingrediente_id']);
        abort_if($ingredienteId === null, 404);

        $pessoa->preferencias()->updateOrCreate(
            ['ingrediente_id' => $ingredienteId],
            ['nivel' => $dados['nivel']]
        );

        return back()->with('status', 'Preferência salva.');
    }

    public function removerPreferencia(Request $request, Pessoa $pessoa, Preferencia $preferencia): RedirectResponse
    {
        $this->autorizar($request, $pessoa);
        abort_unless($preferencia->pessoa_id === $pessoa->id, 404);
        $preferencia->delete();

        return back()->with('status', 'Preferência removida.');
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, Pessoa $pessoa): void
    {
        abort_unless($pessoa->casa_id === $this->casa($request)->id, 404);

        $atual = $request->attributes->get('pessoaAtual');
        abort_unless($atual->tipo === 'adulto' || $atual->id === $pessoa->id, 403);
    }

    private function ingredienteDaCasa(Request $request, mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $this->casa($request)->ingredientes()->whereKey($id)->value('id');
    }
}

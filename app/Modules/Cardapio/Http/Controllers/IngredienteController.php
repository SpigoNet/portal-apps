<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\CategoriaIngrediente;
use App\Modules\Cardapio\Models\Ingrediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IngredienteController extends Controller
{
    public function index(Request $request): View
    {
        return view('Cardapio::ingredientes.index', [
            'ingredientes' => $this->casa($request)->ingredientes()->with('categoria')->orderBy('nome')->get(),
        ]);
    }

    public function create(): View
    {
        return view('Cardapio::ingredientes.form', [
            'ingrediente' => new Ingrediente(['unidade_base' => 'g']),
            'categorias' => CategoriaIngrediente::orderBy('ordem')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->casa($request)->ingredientes()->create($this->validar($request, null));

        return redirect()->route('cardapio.ingredientes.index')->with('status', 'Ingrediente cadastrado.');
    }

    public function edit(Request $request, Ingrediente $ingrediente): View
    {
        $this->autorizar($request, $ingrediente);

        return view('Cardapio::ingredientes.form', [
            'ingrediente' => $ingrediente,
            'categorias' => CategoriaIngrediente::orderBy('ordem')->get(),
            'outros' => $this->casa($request)->ingredientes()->whereKeyNot($ingrediente->id)->orderBy('nome')->get(),
            'substitutos' => $ingrediente->substituicoes()->pluck('substituto_id')->all(),
        ]);
    }

    public function update(Request $request, Ingrediente $ingrediente): RedirectResponse
    {
        $this->autorizar($request, $ingrediente);
        $ingrediente->update($this->validar($request, $ingrediente));
        $this->sincronizarSubstitutos($request, $ingrediente);

        return redirect()->route('cardapio.ingredientes.index')->with('status', 'Ingrediente atualizado.');
    }

    public function destroy(Request $request, Ingrediente $ingrediente): RedirectResponse
    {
        $this->autorizar($request, $ingrediente);
        $ingrediente->delete();

        return redirect()->route('cardapio.ingredientes.index')->with('status', 'Ingrediente removido.');
    }

    private function sincronizarSubstitutos(Request $request, Ingrediente $ingrediente): void
    {
        $ids = $this->casa($request)->ingredientes()
            ->whereKeyNot($ingrediente->id)
            ->whereIn('id', (array) $request->input('substitutos', []))
            ->pluck('id');

        $ingrediente->substituicoes()->whereNotIn('substituto_id', $ids)->delete();

        foreach ($ids as $id) {
            $ingrediente->substituicoes()->firstOrCreate(
                ['substituto_id' => $id],
                ['casa_id' => $ingrediente->casa_id]
            );
        }
    }

    private function casa(Request $request): Casa
    {
        return $request->attributes->get('casa');
    }

    private function autorizar(Request $request, Ingrediente $ingrediente): void
    {
        abort_unless($ingrediente->casa_id === $this->casa($request)->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Ingrediente $ingrediente): array
    {
        return $request->validate([
            'nome' => [
                'required', 'string', 'max:120',
                Rule::unique('cardapio_ingredientes', 'nome')->where('casa_id', $this->casa($request)->id)->ignore($ingrediente?->id),
            ],
            'categoria_id' => ['nullable', 'integer', 'exists:cardapio_categorias_ingrediente,id'],
            'unidade_base' => ['required', Rule::in(['g', 'ml', 'un'])],
        ]);
    }
}

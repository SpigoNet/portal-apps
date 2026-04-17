<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VocabularioControlado\Models\ListaValores;
use App\Modules\VocabularioControlado\Models\Vocabulario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $termos = Vocabulario::orderBy('palavra')->get();

        return view('VocabularioControlado::admin.index', compact('termos'));
    }

    public function criar(): View
    {
        return view('VocabularioControlado::admin.criar');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'palavra' => 'required|string|max:100',
        ]);

        Vocabulario::create([
            'palavra' => $request->input('palavra'),
            'resumo' => '',
            'status' => 'Disponível',
            'unidade' => '',
            'funcao' => '',
        ]);

        return redirect()->route('vocabulario-controlado.admin.index')
            ->with('success', 'Termo adicionado com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Vocabulario::findOrFail($id)->delete();

        return redirect()->route('vocabulario-controlado.admin.index')
            ->with('success', 'Termo excluído com sucesso.');
    }

    public function listas(): View
    {
        $nomeListas = \DB::table('nomeListas')->get();

        $listaValores = ListaValores::orderBy('value_pairs_name')
            ->orderBy('displayed_value')
            ->get()
            ->groupBy('value_pairs_name');

        return view('VocabularioControlado::admin.listas', compact('nomeListas', 'listaValores'));
    }
}

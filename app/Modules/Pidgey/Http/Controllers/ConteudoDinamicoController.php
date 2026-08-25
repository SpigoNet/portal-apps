<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Pidgey\Models\ConteudoDinamico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConteudoDinamicoController
{
    public function index(): View
    {
        $conteudos = ConteudoDinamico::query()->orderBy('nome')->get();

        return view('Pidgey::conteudos.index', compact('conteudos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:texto',
            'conteudo' => 'nullable|string',
        ]);

        ConteudoDinamico::create([
            'nome' => $data['nome'],
            'tipo' => 'texto',
            'conteudo' => $data['conteudo'] ?? null,
            'ativo' => true,
            'sistema' => false,
        ]);

        return redirect()->route('pidgey.conteudos.index')
            ->with('success', 'Conteúdo dinâmico criado com sucesso.');
    }

    public function destroy(ConteudoDinamico $conteudo): RedirectResponse
    {
        $conteudo->delete();

        return redirect()->route('pidgey.conteudos.index')
            ->with('success', 'Conteúdo dinâmico removido.');
    }
}

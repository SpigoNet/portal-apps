<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VocabularioControlado\Models\Vocabulario;
use Illuminate\Http\Request;

class PesquisaController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('VocabularioControlado::pesquisa.index');
    }

    public function buscar(Request $request): \Illuminate\View\View
    {
        $request->validate([
            'palavra' => 'required|string|max:200',
        ]);

        $termo = $request->input('palavra');

        $resultados = Vocabulario::where('palavra', 'LIKE', '%'.$termo.'%')
            ->orderBy('palavra')
            ->get();

        return view('VocabularioControlado::pesquisa.resultados', compact('resultados', 'termo'));
    }
}

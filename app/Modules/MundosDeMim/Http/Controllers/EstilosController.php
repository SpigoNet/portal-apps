<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Theme;

class EstilosController extends Controller
{
    public function index()
    {
        // Lista temas agrupados por tipo (Sazonal vs Padrão)
        $sazonais = Theme::where('is_seasonal', true)->get();
        $padroes = Theme::where('is_seasonal', false)->get();

        return view('MundosDeMim::estilos.index', compact('sazonais', 'padroes'));
    }
}

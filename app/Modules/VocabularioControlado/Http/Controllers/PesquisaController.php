<?php

namespace App\Modules\VocabularioControlado\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\VocabularioControlado\Models\Vocabulario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesquisaController extends Controller
{
    /**
     * Lista pública. Com ?palavra= realiza a busca inline na mesma página.
     */
    public function index(Request $request): View
    {
        $termo = trim((string) $request->query('palavra', ''));

        $resultados = null;

        if ($termo !== '') {
            $resultados = Vocabulario::where('palavra', 'LIKE', '%'.$termo.'%')
                ->orderBy('palavra')
                ->get();
        }

        return view('VocabularioControlado::pesquisa.index', compact('termo', 'resultados'));
    }
}

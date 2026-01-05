<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use Illuminate\Support\Facades\Auth;

class GaleriaController extends Controller
{
    public function index()
    {
        // Busca o histórico ordenado da mais recente para a mais antiga
        $generations = DailyGeneration::with('theme')
            ->where('user_id', Auth::id())
            ->orderBy('reference_date', 'desc')
            ->paginate(12); // Paginação de 12 itens por página

        return view('MundosDeMim::galeria.index', compact('generations'));
    }
}

<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use Illuminate\Http\Request;
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

    public function rate(Request $request, DailyGeneration $generation)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link de avaliação inválido ou expirado.');
        }

        $rating = $request->input('rating');
        if ($rating >= 1 && $rating <= 5) {
            $generation->update(['rating' => $rating]);
        }

        // Loga o usuário automaticamente através do token (signed route)
        Auth::login($generation->user);

        session()->flash('success', 'Avaliação registrada com sucesso! Bem-vindo de volta.');

        return redirect()->route('mundos-de-mim.galeria.index');
    }
}

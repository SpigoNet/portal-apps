<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstilosController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Busca temas com seus exemplos de resultado
        // Ordena Sazonais primeiro
        $themes = Theme::with('examples')
            ->orderBy('is_seasonal', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        // Identifica quais IDs o usuário já segue/habilitou
        // Se o usuário nunca interagiu, assumimos que ele quer TODOS (ou nenhum, dependendo da regra)
        // Aqui vou assumir: Se não existe na tabela, está DESATIVADO por padrão (opt-in)
        $userEnabledThemes = $user->belongsToMany(Theme::class, 'mundos_de_mim_user_themes')
            ->wherePivot('is_enabled', true)
            ->pluck('mundos_de_mim_themes.id')
            ->toArray();

        return view('MundosDeMim::estilos.index', compact('themes', 'userEnabledThemes'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:mundos_de_mim_themes,id',
        ]);

        $user = Auth::user();
        $themeId = $request->theme_id;

        // Verifica status atual
        $exists = $user->belongsToMany(Theme::class, 'mundos_de_mim_user_themes')
            ->wherePivot('theme_id', $themeId)
            ->first();

        if ($exists) {
            // Se existe, inverte o valor de is_enabled
            $newStatus = !$exists->pivot->is_enabled;
            $user->belongsToMany(Theme::class, 'mundos_de_mim_user_themes')
                ->updateExistingPivot($themeId, ['is_enabled' => $newStatus]);
        } else {
            // Se não existe, cria como habilitado
            $user->belongsToMany(Theme::class, 'mundos_de_mim_user_themes')
                ->attach($themeId, ['is_enabled' => true]);
            $newStatus = true;
        }

        $msg = $newStatus ? 'Tema ativado! Você poderá receber artes deste estilo.' : 'Tema pausado. Você não receberá mais este estilo.';

        return back()->with('success', $msg); // Pode ser trocado por resposta JSON se usar AJAX
    }
}

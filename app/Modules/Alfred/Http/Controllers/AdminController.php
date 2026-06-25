<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\Medicamento;
use App\Modules\Alfred\Models\Rotina;
use App\Modules\Alfred\Models\RotinaCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total_rotinas' => Rotina::doUsuario($user->id)->count(),
            'rotinas_ativas' => Rotina::doUsuario($user->id)->where('ativa', true)->count(),
            'total_categorias' => RotinaCategoria::count(),
            'total_medicamentos' => Medicamento::doUsuario($user->id)->count(),
        ];

        return view('Alfred::admin.index', compact('stats'));
    }

    public function categoriasRotina()
    {
        $categorias = RotinaCategoria::orderBy('ordem')->orderBy('nome')->get();

        return view('Alfred::admin.categorias-rotina', compact('categorias'));
    }

    public function storeCategoriaRotina(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:alfred_rotina_categorias,nome',
            'cor' => 'required|string|max:7',
            'icone' => 'nullable|string|max:50',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $validated['ativa'] = true;

        RotinaCategoria::create($validated);

        return redirect()->route('alfred.admin.categorias-rotina')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function updateCategoriaRotina(Request $request, RotinaCategoria $categoria)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:alfred_rotina_categorias,nome,'.$categoria->id,
            'cor' => 'required|string|max:7',
            'icone' => 'nullable|string|max:50',
            'descricao' => 'nullable|string',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $categoria->update($validated);

        return redirect()->route('alfred.admin.categorias-rotina')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroyCategoriaRotina(RotinaCategoria $categoria)
    {
        $categoria->delete();

        return redirect()->route('alfred.admin.categorias-rotina')
            ->with('success', 'Categoria excluída com sucesso!');
    }

    public function configuracoes()
    {
        $user = Auth::user();
        $profile = $user->profile;

        $configuracoes = [
            'meta_agua' => $profile?->meta_agua_ml ?? 2500,
            'modo_dia_ruim_ativo' => $profile?->modo_dia_ruim ?? false,
            'treetask_url' => $profile?->treetask_url ?? 'https://apps.spigo.net',
            'treetask_user_id' => $profile?->treetask_user_id ?? '',
            'treetask_token' => $profile?->treetask_token ?? '',
        ];

        return view('Alfred::admin.configuracoes', compact('configuracoes'));
    }

    public function updateConfiguracoes(Request $request)
    {
        $validated = $request->validate([
            'meta_agua' => 'required|integer|min:500|max:5000',
            'treetask_url' => 'nullable|url',
            'treetask_user_id' => 'nullable|string',
            'treetask_token' => 'nullable|string',
        ]);

        $user = Auth::user();

        if ($user->profile) {
            $user->profile->update([
                'meta_agua_ml' => $validated['meta_agua'],
                'treetask_url' => $validated['treetask_url'] ?? 'https://apps.spigo.net',
                'treetask_user_id' => $validated['treetask_user_id'] ?? '',
                'treetask_token' => $validated['treetask_token'] ?? '',
            ]);
        } else {
            $user->profile()->create([
                'meta_agua_ml' => $validated['meta_agua'],
                'treetask_url' => $validated['treetask_url'] ?? 'https://apps.spigo.net',
                'treetask_user_id' => $validated['treetask_user_id'] ?? '',
                'treetask_token' => $validated['treetask_token'] ?? '',
            ]);
        }

        return redirect()->route('alfred.admin.configuracoes')
            ->with('success', 'Configurações atualizadas!');
    }
}

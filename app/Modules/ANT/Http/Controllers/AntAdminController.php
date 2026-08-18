<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Services\SemestreService;
use Illuminate\Http\Request;

class AntAdminController extends Controller
{
    public function index()
    {
        $config = AntConfiguracao::first();
        if (! $config || ! $config->isAdmin(auth()->user()->email)) {
            abort(403, 'Acesso não autorizado.');
        }

        $semestreAtual = SemestreService::getCurrent();
        $semestresDisponiveis = SemestreService::getAvailable();

        return view('ANT::admin.dashboard', compact('semestreAtual', 'config', 'semestresDisponiveis'));
    }

    public function updateSemestre(Request $request)
    {
        $config = AntConfiguracao::first();
        if (! $config || ! $config->isAdmin(auth()->user()->email)) {
            abort(403, 'Acesso não autorizado.');
        }

        $request->validate([
            'semestre_atual' => 'required|string|max:6|regex:/^\d{4}[\/-][12]$/',
        ]);

        SemestreService::setCurrent(str_replace('-', '/', $request->semestre_atual));

        return redirect()->route('ant.admin.home')
            ->with('success', "Semestre alterado para {$request->semestre_atual} com sucesso!");
    }
}

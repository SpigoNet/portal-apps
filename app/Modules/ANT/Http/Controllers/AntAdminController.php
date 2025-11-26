<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntConfiguracao;
use Illuminate\Http\Request;

class AntAdminController extends Controller
{
    public function index()
    {
        // Verifica permissão (redundância de segurança)
        $config = AntConfiguracao::first();
        if (!$config || !$config->isAdmin(auth()->user()->email)) {
            abort(403, 'Acesso não autorizado.');
        }

        $semestreAtual = $config->semestre_atual;

        return view('ANT::admin.dashboard', compact('semestreAtual', 'config'));
    }
}

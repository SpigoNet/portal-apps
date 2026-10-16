<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Modules\MundosDeMim\Models\ParametroImagem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminParametrosImagemController extends Controller
{
    public function index(): View
    {
        $provedores = AiProvider::where('is_active', true)->orderBy('nome')->get()
            ->filter(fn (AiProvider $p): bool => $p->driver === 'openai')
            ->values();

        $parametros = ParametroImagem::whereIn('ai_provedor_id', $provedores->pluck('id'))
            ->get()->keyBy('ai_provedor_id');

        return view('MundosDeMim::admin.parametros-imagem.index', [
            'provedores' => $provedores,
            'parametros' => $parametros,
            'opcoes' => ParametroImagem::OPCOES,
        ]);
    }

    public function update(Request $request, int $provedor): RedirectResponse
    {
        $modelo = AiProvider::findOrFail($provedor);
        $validos = ParametroImagem::sanitizar($request->all());

        if ($validos === []) {
            ParametroImagem::where('ai_provedor_id', $modelo->id)->delete();

            return back()->with('success', "Parâmetros de {$modelo->nome} limpos.");
        }

        ParametroImagem::updateOrCreate(['ai_provedor_id' => $modelo->id], ['parametros' => $validos]);

        return back()->with('success', "Parâmetros de {$modelo->nome} salvos.");
    }
}

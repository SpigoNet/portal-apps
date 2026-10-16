<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Support\TipoRefeicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CasaController extends Controller
{
    public function criar(Request $request): View|RedirectResponse
    {
        if (Pessoa::where('user_id', $request->user()->id)->where('ativo', true)->exists()) {
            return redirect()->route('cardapio.index');
        }

        return view('Cardapio::casa.criar');
    }

    public function salvar(Request $request): RedirectResponse
    {
        if (Pessoa::where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('cardapio.index');
        }

        $dados = $request->validate(['nome' => ['required', 'string', 'max:120']]);

        DB::transaction(function () use ($dados, $request): void {
            $casa = Casa::create([
                'nome' => $dados['nome'],
                'refeicoes_ativas' => TipoRefeicao::padrao(),
            ]);

            $casa->pessoas()->create([
                'user_id' => $request->user()->id,
                'nome' => $request->user()->name,
                'tipo' => 'adulto',
            ]);
        });

        return redirect()->route('cardapio.index')->with('status', 'Casa criada. Agora cadastre as pessoas que comem aqui.');
    }
}

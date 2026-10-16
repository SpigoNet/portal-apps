<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemCompra;
use App\Modules\Cardapio\Services\EstoqueService;
use App\Modules\Cardapio\Services\PlanejadorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __construct(private readonly EstoqueService $estoque) {}

    public function index(Request $request): View
    {
        /** @var Casa $casa */
        $casa = $request->attributes->get('casa');
        $segunda = PlanejadorService::segundaDaSemana(now());

        $planoAtual = $casa->planos()->whereDate('inicio_semana', $segunda)->first();
        $proximo = $casa->planos()->whereDate('inicio_semana', $segunda->copy()->addWeek())->first();

        return view('Cardapio::index', [
            'casa' => $casa,
            'pessoas' => $casa->pessoas()->where('ativo', true)->get(),
            'hoje' => $planoAtual?->refeicoes()->whereDate('data', today())->with(['receita', 'pessoas'])->get()
                ->sortBy(fn ($r) => array_search($r->tipo_refeicao, array_keys(\App\Modules\Cardapio\Support\TipoRefeicao::rotulos()))) ?? collect(),
            'planoAtual' => $planoAtual,
            'proximoPlano' => $proximo,
            'proximaSemana' => $segunda->copy()->addWeek(),
            'eventosProximos' => $casa->eventos()->whereDate('data', '>=', today())->whereDate('data', '<=', today()->addDays(7))->orderBy('data')->get(),
            'ausenciasProximas' => $casa->ausencias()->with('pessoa')->whereDate('inicio', '<=', today()->addDays(7))->whereDate('fim', '>=', today())->orderBy('inicio')->get(),
            'vencendo' => $this->estoque->vencendo($casa),
            'estoqueBaixo' => $this->estoque->estoqueBaixo($casa),
            'totalEstoque' => $casa->itensEstoque()->distinct()->count('ingrediente_id'),
            'itensParaComprar' => ItemCompra::whereIn('lista_id', $casa->listasCompras()->where('status', 'aberta')->pluck('id'))->where('comprado', false)->count(),
        ]);
    }
}

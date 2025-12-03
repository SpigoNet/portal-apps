<?php

namespace App\Modules\Metricas\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metricas\Models\MetricaAcesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricasController extends Controller
{
    /**
     * Exibe o dashboard de métricas.
     */
    public function index()
    {
        // 1. Contagem de acessos por módulo
        $acessosPorModulo = MetricaAcesso::select('modulo_nome', DB::raw('count(*) as total'))
            ->groupBy('modulo_nome')
            ->orderByDesc('total')
            ->get();

        // 2. Últimos 50 acessos detalhados (com paginação simples se preferir, aqui usaremos get para simplicidade)
        $ultimosAcessos = MetricaAcesso::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // 3. Usuários mais ativos
        $usuariosAtivos = MetricaAcesso::select('user_id', DB::raw('count(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('user')
            ->get();

        return view('Metricas::index', compact('acessosPorModulo', 'ultimosAcessos', 'usuariosAtivos'));
    }
}

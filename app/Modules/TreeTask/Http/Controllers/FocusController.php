<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;

class FocusController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = Tarefa::with(['fase.projeto'])
            ->where('id_user_responsavel', $userId)
            ->where('status', '!=', 'Concluído'); // Otimização: não buscar concluídas desnecessariamente

        // Filtro por Projeto
        if ($request->filled('projeto')) {
            $query->whereHas('fase.projeto', function ($q) use ($request) {
                $q->where('id_projeto', $request->input('projeto'));
            });
        }

        // Filtro por Prioridade
        if ($request->filled('prioridade')) {
            $query->where('prioridade', $request->input('prioridade'));
        }

        // Ordenação
        $ordenar = $request->input('ordenar', 'prioridade');
        $ordemPrioridade = "CASE prioridade WHEN 'Urgente' THEN 1 WHEN 'Alta' THEN 2 WHEN 'Média' THEN 3 WHEN 'Baixa' THEN 4 ELSE 5 END";
        switch ($ordenar) {
            case 'vencimento':
                $query->orderBy('data_vencimento', 'asc')
                    ->orderBy('ordem_global', 'asc');
                break;
            case 'ordem':
                $query->orderBy('ordem_global', 'asc')
                    ->orderByRaw($ordemPrioridade);
                break;
            case 'prioridade':
            default:
                $ordenar = 'prioridade';
                $query->orderByRaw($ordemPrioridade)
                    ->orderBy('data_vencimento', 'asc')
                    ->orderBy('ordem_global', 'asc');
                break;
        }

        $tarefas = $query->get();

        // 1. O Hiperfoco (A Única Coisa que Importa)
        // Se houver algo em andamento, o sistema entra em modo "Túnel"
        $focoTotal = $tarefas->first(fn ($t) => $t->status === 'Em Andamento');

        // 2. Aguardando Resposta (Bloqueios)
        $aguardando = $tarefas->filter(fn ($t) => $t->status === 'Aguardando resposta');

        // 3. Backlog (O resto, que deve ficar oculto se houver foco total)
        $statusFiltro = $request->input('status', 'todos');
        $aFazer = $tarefas->filter(function ($t) use ($statusFiltro) {
            if ($statusFiltro !== 'todos') {
                return $t->status === $statusFiltro;
            }

            return $t->status === 'A Fazer' || $t->status === 'Planejamento';
        });

        // 4. Concluídas recentes (apenas para histórico rápido)
        $concluidas = Tarefa::where('id_user_responsavel', $userId)
            ->where('status', 'Concluído')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        $projetos = Projeto::where('id_user_owner', $userId)
            ->orderBy('nome')
            ->get();

        $prioridades = ['Urgente', 'Alta', 'Média', 'Baixa'];

        $filtrosAtivos = $request->filled('projeto')
            || $request->filled('prioridade')
            || $statusFiltro !== 'todos'
            || $ordenar !== 'prioridade';

        return view('TreeTask::focus.index', compact(
            'focoTotal',
            'aguardando',
            'aFazer',
            'concluidas',
            'projetos',
            'prioridades',
            'statusFiltro',
            'ordenar',
            'filtrosAtivos'
        ));
    }
}

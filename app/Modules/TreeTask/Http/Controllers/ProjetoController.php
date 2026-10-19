<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Support\ResumoProjeto;
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    public function index()
    {
        $projetos = Projeto::with(['owner', 'fases.tarefas.responsavel'])
            ->where('id_user_owner', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $resumos = $projetos->mapWithKeys(fn (Projeto $p) => [$p->id_projeto => ResumoProjeto::de($p)]);

        $tarefas = $projetos->flatMap->fases->flatMap->tarefas;
        $abertas = $tarefas->where('status', '!=', Tarefa::CONCLUIDA);
        $fimDaSemana = now()->endOfWeek();

        $stats = [
            'andamento' => $tarefas->where('status', 'Em Andamento')->count(),
            'atrasadas' => $resumos->sum('atrasadas'),
            'semana' => $abertas->filter(fn (Tarefa $t) => $t->data_vencimento !== null
                && $t->data_vencimento->betweenIncluded(now()->startOfDay(), $fimDaSemana))->count(),
            'concluidas' => $tarefas->filter(fn (Tarefa $t) => $t->status === Tarefa::CONCLUIDA
                && $t->updated_at->gte(now()->subDays(7)))->count(),
        ];

        return view('TreeTask::index', compact('projetos', 'resumos', 'stats'));
    }

    public function create()
    {
        return view('TreeTask::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'required|string',
            'data_inicio' => 'nullable|date',
            'data_prevista_termino' => 'nullable|date|after_or_equal:data_inicio',
        ]);

        // Define o dono como o usuário logado e status padrão
        $data = array_merge($validated, [
            'id_user_owner' => auth()->id(),
            'status' => 'Planejamento',
        ]);

        Projeto::create($data);

        return redirect()->route('treetask.index')
            ->with('success', 'Projeto criado com sucesso.');
    }

    /**
     * O quadro por projeto foi substituído pelo kanban geral (colunas por status), já filtrado pelo projeto.
     */
    public function show($id)
    {
        $projeto = Projeto::findOrFail($id);

        if ($projeto->id_user_owner !== auth()->id()) {
            abort(403);
        }

        return redirect()->route('treetask.kanban', ['projeto' => [$projeto->id_projeto]]);
    }

    /**
     * Visão em lista: fases em ordem, cada uma com as suas tarefas.
     */
    public function lista($id)
    {
        $projeto = Projeto::with([
            'fases' => fn ($q) => $q->orderBy('ordem'),
            'fases.tarefas' => fn ($q) => $q->orderBy('ordem'),
            'fases.tarefas.responsavel',
        ])->findOrFail($id);

        if ($projeto->id_user_owner !== auth()->id()) {
            abort(403);
        }

        $resumo = ResumoProjeto::de($projeto);

        $proximos = $projeto->fases->flatMap->tarefas
            ->where('status', '!=', Tarefa::CONCLUIDA)
            ->filter(fn (Tarefa $t) => $t->data_vencimento !== null)
            ->sortBy('data_vencimento')
            ->take(3);

        return view('TreeTask::lista', compact('projeto', 'resumo', 'proximos'));
    }

    public function treeView($id)
    {
        $projeto = Projeto::with([
            'fases' => fn ($q) => $q->orderBy('ordem'),
            'fases.tarefas' => fn ($q) => $q->orderBy('ordem'),
            'fases.tarefas.responsavel',
        ])->findOrFail($id);

        if ($projeto->id_user_owner !== auth()->id()) {
            abort(403);
        }

        return view('TreeTask::tree', compact('projeto'));
    }
}

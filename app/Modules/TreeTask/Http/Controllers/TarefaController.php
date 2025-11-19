<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Tarefa;
use App\Models\User; // Modelo global de usuário
use Illuminate\Http\Request;

class TarefaController extends Controller
{
    private $statusList = 'A Fazer,Em Andamento,Concluído,Planejamento,Aguardando resposta';
    public function create($id_fase)
    {
        $fase = Fase::with('projeto')->findOrFail($id_fase);

        // Precisamos listar usuários para selecionar o responsável
        $users = User::all(['id', 'name']);

        return view('TreeTask::tarefas.create', compact('fase', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_fase' => 'required|exists:treetask_fases,id_fase',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'id_user_responsavel' => 'required|exists:users,id',
            'prioridade' => 'nullable|string|in:Baixa,Média,Alta,Urgente',
            'data_vencimento' => 'nullable|date',
            'estimativa_tempo' => 'nullable|numeric',
        ]);

        // Cria a tarefa
        $tarefa = Tarefa::create($validated);

        // Redireciona de volta para o Board do Projeto
        // Precisamos buscar o ID do projeto através da fase
        $projetoId = $tarefa->fase->id_projeto;

        return redirect()->route('treetask.show', $projetoId)
            ->with('success', 'Tarefa criada com sucesso.');
    }
    /**
     * Exibe o formulário de edição.
     */
    public function edit($id)
    {
        $tarefa = Tarefa::with('fase')->findOrFail($id);

        // Busca todas as fases DO MESMO PROJETO para permitir a movimentação
        $fases = Fase::where('id_projeto', $tarefa->fase->id_projeto)
            ->orderBy('ordem')
            ->get();

        $users = User::all(['id', 'name']);

        return view('TreeTask::tarefas.edit', compact('tarefa', 'fases', 'users'));
    }

    /**
     * Atualiza a tarefa (incluindo mudança de fase).
     */
    public function update(Request $request, $id)
    {
        $tarefa = Tarefa::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'id_fase' => 'required|exists:treetask_fases,id_fase', // <--- Aqui acontece a mágica da movimentação
            'id_user_responsavel' => 'required|exists:users,id',
            'prioridade' => 'nullable|string',
            'status' => 'required|string|in:' . $this->statusList,
            'data_vencimento' => 'nullable|date',
            'estimativa_tempo' => 'nullable|numeric',
        ]);

        // Verifica se a fase pertence ao mesmo projeto (segurança)
        $novaFase = Fase::findOrFail($validated['id_fase']);
        if($novaFase->id_projeto != $tarefa->fase->id_projeto) {
            abort(403, 'Não é possível mover a tarefa para uma fase de outro projeto.');
        }

        $tarefa->update($validated);

        // Seus triggers de banco de dados (TRG_treetask_tarefa_au) rodarão automaticamente aqui
        // para atualizar o status da fase e do projeto.

        if ($request->input('origin') === 'focus') {
            return redirect()->route('treetask.focus.index')
                ->with('success', 'Tarefa atualizada (Modo Foco).');
        }

        return redirect()->route('treetask.show', $tarefa->fase->id_projeto)
            ->with('success', 'Tarefa atualizada com sucesso.');
    }
    /**
     * Atualiza apenas o status da tarefa (Ação Rápida)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . $this->statusList,
        ]);

        $tarefa = Tarefa::findOrFail($id);
        $tarefa->update(['status' => $request->status]);

        // Redireciona de volta para a lista Zen
        return redirect()->route('treetask.focus.index')
            ->with('success', "Status alterado para: {$request->status}");
    }
    public function show($id)
    {
        $tarefa = Tarefa::with(['fase', 'responsavel'])->findOrFail($id);
        return view('TreeTask::tarefas.show', compact('tarefa'));
    }
}

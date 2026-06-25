<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;

class TarefaController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $ordenarPor = $request->input('ordenar', 'prioridade');
        $agruparPor = $request->input('agrupar', 'nenhum');

        $tarefas = collect([]);
        $tarefasAgrupadas = null;

        try {
            $query = Tarefa::with('fase.projeto.responsavel')
                ->whereHas('fase.projeto', fn ($q) => $q->where('id_user_owner', $user->id))
                ->whereNotIn('status', ['Concluído'])
                ->orderByRaw("CASE prioridade WHEN 'Urgente' THEN 4 WHEN 'Alta' THEN 3 WHEN 'Média' THEN 2 WHEN 'Baixa' THEN 1 ELSE 0 END DESC")
                ->orderByRaw('ISNULL(data_vencimento), data_vencimento ASC');

            $tarefas = $query->get()->map(fn ($t) => $this->formatTarefa($t));

            $tarefas = $this->aplicarOrdenacao($tarefas, $ordenarPor);

            if ($agruparPor !== 'nenhum') {
                $tarefasAgrupadas = $this->aplicarAgrupamento($tarefas, $agruparPor);
            }
        } catch (\Throwable $e) {
            //
        }

        return view('Alfred::tarefas.index', compact(
            'tarefas',
            'tarefasAgrupadas',
            'ordenarPor',
            'agruparPor'
        ));
    }

    public function atualizarPrioridade(Request $request, $id)
    {
        $request->validate([
            'prioridade' => 'required|integer|in:1,2,3,4',
        ]);

        $mapa = [1 => 'Baixa', 2 => 'Média', 3 => 'Alta', 4 => 'Urgente'];

        try {
            $tarefa = Tarefa::findOrFail($id);
            $tarefa->update(['prioridade' => $mapa[$request->prioridade]]);

            return redirect()->back()->with('success', 'Prioridade atualizada com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar prioridade.');
        }
    }

    public function atualizarStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:A Fazer,Planejamento,Em Andamento,Aguardando resposta,Concluído',
        ]);

        try {
            $tarefa = Tarefa::findOrFail($id);
            $tarefa->update(['status' => $request->status]);

            return redirect()->back()->with('success', 'Status atualizado com sucesso!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar status.');
        }
    }

    private function formatTarefa($tarefa): object
    {
        $projeto = $tarefa->fase?->projeto;
        $prioridadeMap = ['Urgente' => 4, 'Alta' => 3, 'Média' => 2, 'Baixa' => 1];

        return (object) [
            'id_tarefa' => $tarefa->id_tarefa,
            'titulo' => $tarefa->titulo ?? 'Sem título',
            'descricao' => $tarefa->descricao ?? '',
            'status' => match ($tarefa->status) {
                'A Fazer', 'Planejamento' => 'pendente',
                'Em Andamento', 'Aguardando resposta' => 'andamento',
                'Concluído' => 'concluida',
                default => 'pendente',
            },
            'status_original' => $tarefa->status ?? 'A Fazer',
            'prioridade' => $prioridadeMap[$tarefa->prioridade] ?? 2,
            'prioridade_original' => $tarefa->prioridade ?? 'Média',
            'data_vencimento' => $tarefa->data_vencimento?->format('Y-m-d'),
            'nome_fase' => $tarefa->fase?->nome ?? 'Sem Fase',
            'nome_projeto' => $projeto?->nome ?? 'Sem Projeto',
            'prazo' => $tarefa->data_vencimento,
            'iniciada_em' => null,
        ];
    }

    private function aplicarOrdenacao($tarefas, string $ordenarPor)
    {
        return match ($ordenarPor) {
            'data' => $tarefas->sortBy(fn ($t) => $t->prazo ?? '9999-12-31'),
            'projeto' => $tarefas->sortBy('nome_projeto'),
            default => $tarefas->sortByDesc('prioridade'),
        };
    }

    private function aplicarAgrupamento($tarefas, string $agruparPor)
    {
        return match ($agruparPor) {
            'fase' => $tarefas->groupBy(fn ($t) => $t->nome_fase ?? 'Sem Fase'),
            default => $tarefas->groupBy(fn ($t) => $t->nome_projeto ?? 'Sem Projeto'),
        };
    }
}

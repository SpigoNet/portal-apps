<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Projeto;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GoodMorningController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // 1. Buscar projetos ativos do usuário
        $projetos = Projeto::where('id_user_owner', $userId)
            ->whereNotIn('status', ['Concluído', 'Cancelado'])
            ->with([
                'fases.tarefas' => function ($q) use ($userId) {
                    // Carregar tarefas para calcular última interação e próximas tarefas
                    // Otimização: Poderíamos carregar apenas dados necessários, mas para MVP isso serve
                    $q->select('id_tarefa', 'id_fase', 'titulo', 'updated_at', 'created_at', 'status', 'data_vencimento', 'prioridade', 'id_user_responsavel');
                }
            ])
            ->get();

        // 2. Calcular 'last_interaction' e preparar dados para a view
        $projetosData = $projetos->map(function ($projeto) {

            // Data de atualização do projeto
            $lastInteraction = $projeto->updated_at;

            // Verificar tarefas para encontrar atualização mais recente
            foreach ($projeto->fases as $fase) {
                foreach ($fase->tarefas as $tarefa) {
                    if ($tarefa->updated_at && $tarefa->updated_at->gt($lastInteraction)) {
                        $lastInteraction = $tarefa->updated_at;
                    }
                }
            }

            // 3. Pegar próximas 3 tarefas
            // Flatten nas tarefas do projeto
            $todasTarefas = $projeto->fases->flatMap->tarefas;

            // Filtrar tarefas pendentes
            $proximasTarefas = $todasTarefas->filter(function ($tarefa) {
                return !in_array($tarefa->status, ['Concluído', 'Cancelado']);
            })->sortBy([
                        ['data_vencimento', 'asc'], // Vencimento mais próximo primeiro
                        ['prioridade', 'desc'],     // Maior prioridade primeiro (se houver lógica numérica, aqui é string, cuidado)
                    ])->take(3);

            $projeto->last_interaction = $lastInteraction;
            $projeto->proximas_tarefas = $proximasTarefas;

            // Calculo de dias sem mexer
            $projeto->days_since_interaction = $lastInteraction ? $lastInteraction->diffInDays(now()) : 999;

            return $projeto;
        });

        // 4. Ordenar projetos pelos mais esquecidos (maior tempo sem interação primeiro)
        $projetosOrdenados = $projetosData->sortByDesc('days_since_interaction');

        return view('TreeTask::good_morning', [
            'projetos' => $projetosOrdenados
        ]);
    }
}

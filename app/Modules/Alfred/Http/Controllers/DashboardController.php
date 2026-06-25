<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\ConsumoAgua;
use App\Modules\Alfred\Models\LogDiaRuim;
use App\Modules\Alfred\Models\Medicamento;
use App\Modules\Alfred\Models\Rotina;
use App\Modules\TreeTask\Models\Tarefa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user?->profile;

        if ($profile?->modo_dia_ruim) {
            return app(DiaRuimController::class)->index();
        }

        $hoje = Carbon::now();
        $diaUtil = $hoje->isWeekday();

        $tarefasPendentes = collect([]);
        $tarefasTop3 = collect([]);

        try {
            $query = Tarefa::with('fase.projeto.responsavel')
                ->whereHas('fase.projeto', fn ($q) => $q->where('id_user_owner', $user->id))
                ->whereNotIn('status', ['Concluído'])
                ->orderByRaw("CASE prioridade WHEN 'Urgente' THEN 4 WHEN 'Alta' THEN 3 WHEN 'Média' THEN 2 WHEN 'Baixa' THEN 1 ELSE 0 END DESC")
                ->orderBy('data_vencimento', 'asc');

            $tarefasPendentes = (clone $query)->take(5)->get()->map(fn ($t) => $this->formatTarefa($t));
            $tarefasTop3 = (clone $query)->take(3)->get()->map(fn ($t) => $this->formatTarefa($t));
        } catch (\Throwable $e) {
            session()->flash('warning', '⚠️ TreeTask offline.');
        }

        $rotinasHoje = Rotina::todasHoje($user->id);
        $rotinasPendentes = $rotinasHoje->where('executada_hoje', false)->where('pulada_hoje', false);

        $progressoAgua = ConsumoAgua::progressoHoje($user->id);
        $medicamentosAlerta = Medicamento::baixoEstoque()->doUsuario($user->id)->get();
        $energia = $profile?->energia_atual ?? 'media';
        $totalDiasRuim = LogDiaRuim::contarEsteMes($user->id);

        return view('Alfred::dashboard', compact(
            'tarefasPendentes',
            'tarefasTop3',
            'rotinasHoje',
            'rotinasPendentes',
            'diaUtil',
            'progressoAgua',
            'medicamentosAlerta',
            'energia',
            'totalDiasRuim',
        ));
    }

    public function atualizarEnergia(Request $request)
    {
        $request->validate(['energia' => 'required|in:baixa,media,alta']);

        $user = Auth::user();
        if ($user && $user->profile) {
            $user->profile->update(['energia_atual' => $request->energia]);
        }

        return redirect()->back()->with('success', 'Nível de energia atualizado!');
    }

    private function formatTarefa($tarefa): object
    {
        $projeto = $tarefa->fase?->projeto;
        $prioridadeMap = ['Urgente' => 4, 'Alta' => 3, 'Média' => 2, 'Baixa' => 1];

        return (object) [
            'id_tarefa' => $tarefa->id_tarefa,
            'titulo' => $tarefa->titulo ?? 'Sem título',
            'descricao' => $tarefa->descricao ?? '',
            'status' => $tarefa->status_codigo ?? match ($tarefa->status) {
                'A Fazer', 'Planejamento' => 'pendente',
                'Em Andamento', 'Aguardando resposta' => 'andamento',
                'Concluído' => 'concluida',
                default => 'pendente',
            },
            'status_original' => $tarefa->status ?? 'A Fazer',
            'prioridade' => $prioridadeMap[$tarefa->prioridade] ?? 2,
            'prioridade_original' => $tarefa->prioridade ?? 'Média',
            'data_vencimento' => $tarefa->data_vencimento?->format('Y-m-d'),
            'data_criacao' => $tarefa->created_at?->format('Y-m-d'),
            'data_atualizacao' => $tarefa->updated_at?->format('Y-m-d'),
            'estimativa_tempo' => $tarefa->estimativa_tempo,
            'ordem' => $tarefa->ordem ?? 0,
            'nome_fase' => $tarefa->fase?->nome ?? 'Sem fase',
            'nome_projeto' => $projeto?->nome ?? 'Sem projeto',
            'prazo' => $tarefa->data_vencimento ? Carbon::parse($tarefa->data_vencimento) : null,
            'iniciada_em' => null,
        ];
    }
}

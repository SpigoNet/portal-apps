<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\RegistroMedicamento;
use App\Modules\Alfred\Models\Rotina;
use Illuminate\Http\Request;

class RotinaController
{
    public function gerenciar()
    {
        $rotinas = Rotina::doUsuario(auth()->id())->orderBy('prioridade', 'desc')->get();

        return view('Alfred::rotinas.manage', compact('rotinas'));
    }

    public function index(Request $request)
    {
        $dataString = $request->input('data');
        $dataSelecionada = $dataString ? \Carbon\Carbon::parse($dataString) : now();
        $dataAmanha = (clone $dataSelecionada)->addDay();
        $dataOntem = (clone $dataSelecionada)->subDay();

        $rotinasHoje = Rotina::todasHoje(auth()->id(), $dataSelecionada);
        $rotinasAmanha = Rotina::todasHoje(auth()->id(), $dataAmanha);

        $pendentesHoje = $rotinasHoje->where('executada_hoje', false)->where('pulada_hoje', false);

        return view('Alfred::rotinas.index', compact('rotinasHoje', 'rotinasAmanha', 'pendentesHoje', 'dataSelecionada', 'dataAmanha', 'dataOntem'));
    }

    public function create()
    {
        return view('Alfred::rotinas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_recorrencia' => 'required|in:diaria,semanal,mensal,unica',
            'categoria' => 'required|in:saude,trabalho,lazer,financeiro,familia,estudo,outro',
            'prioridade' => 'required|integer|min:1|max:3',
            'horario_sugerido' => 'nullable|date_format:H:i',
        ]);

        $config = [];

        switch ($validated['tipo_recorrencia']) {
            case 'semanal':
                $config['dias_semana'] = $request->input('dias_semana', []);
                break;
            case 'mensal':
                $config['dia_mes'] = $request->input('dia_mes', 1);
                break;
            case 'unica':
                $config['data'] = $request->input('data_unica');
                break;
        }

        $validated['config_recorrencia'] = $config;
        $validated['user_id'] = auth()->id();

        Rotina::create($validated);

        return redirect()->route('alfred.admin.rotinas')
            ->with('success', 'Rotina criada com sucesso!');
    }

    public function show(Rotina $rotina)
    {
        return view('Alfred::rotinas.show', compact('rotina'));
    }

    public function edit(Rotina $rotina)
    {
        return view('Alfred::rotinas.edit', compact('rotina'));
    }

    public function update(Request $request, Rotina $rotina)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_recorrencia' => 'required|in:diaria,semanal,mensal,unica',
            'categoria' => 'required|in:saude,trabalho,lazer,financeiro,familia,estudo,outro',
            'prioridade' => 'required|integer|min:1|max:3',
            'horario_sugerido' => 'nullable|date_format:H:i',
            'ativa' => 'boolean',
        ]);

        $config = [];

        switch ($validated['tipo_recorrencia']) {
            case 'semanal':
                $config['dias_semana'] = $request->input('dias_semana', []);
                break;
            case 'mensal':
                $config['dia_mes'] = $request->input('dia_mes', 1);
                break;
            case 'unica':
                $config['data'] = $request->input('data_unica');
                break;
        }

        $validated['config_recorrencia'] = $config;
        $validated['ativa'] = $request->boolean('ativa', true);

        $rotina->update($validated);

        return redirect()->route('alfred.admin.rotinas')
            ->with('success', 'Rotina atualizada com sucesso!');
    }

    public function destroy(Rotina $rotina)
    {
        $rotina->delete();

        return redirect()->route('alfred.admin.rotinas')
            ->with('success', 'Rotina removida com sucesso!');
    }

    public function marcarExecutada(Rotina $rotina, Request $request)
    {
        $observacao = $request->input('observacao');
        $data = $request->input('data');

        if ($data && \Carbon\Carbon::parse($data)->isFuture()) {
            return redirect()->back()
                ->with('error', 'Não é possível marcar rotinas para datas futuras.');
        }

        if ($rotina->foiExecutadaHoje($data)) {
            return redirect()->back()
                ->with('warning', 'Esta rotina já foi marcada como executada nesta data!');
        }

        if ($rotina->foiPuladaHoje($data)) {
            $rotina->desfazerPuloHoje($data);
        }

        $rotina->marcarExecutada($observacao, $data);

        $msg = 'Rotina "'.$rotina->titulo.'" concluída';
        if ($data) {
            $msg .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
        }
        $msg .= '! 🎉';

        return redirect()->back()
            ->with('success', $msg);
    }

    public function desfazerExecucao(Rotina $rotina, Request $request)
    {
        $data = $request->input('data');
        $dataQuery = $data ? \Carbon\Carbon::parse($data)->format('Y-m-d') : today()->format('Y-m-d');

        $execucao = $rotina->execucoes()
            ->whereDate('data_execucao', $dataQuery)
            ->first();

        if ($execucao) {
            $execucao->delete();

            $msg = 'Execução desfeita';
            if ($data) {
                $msg .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
            }
            $msg .= '!';

            return redirect()->back()
                ->with('success', $msg);
        }

        return redirect()->back()
            ->with('warning', 'Não há execução para desfazer nesta data.');
    }

    public function pularHoje(Rotina $rotina, Request $request)
    {
        $motivo = $request->input('motivo');
        $data = $request->input('data');

        if ($data && \Carbon\Carbon::parse($data)->isFuture()) {
            return redirect()->back()
                ->with('error', 'Não é possível pular rotinas para datas futuras.');
        }

        if ($rotina->foiExecutadaHoje($data)) {
            return redirect()->back()
                ->with('warning', 'Esta rotina já foi executada nesta data!');
        }

        if ($rotina->foiPuladaHoje($data)) {
            return redirect()->back()
                ->with('warning', 'Esta rotina já foi pulada nesta data!');
        }

        $rotina->pularHoje($motivo, $data);

        $msg = 'Rotina pulada';
        if ($data) {
            $msg .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
        } else {
            $msg .= ' para hoje';
        }
        if ($motivo) {
            $msg .= '. Motivo: '.$motivo;
        }

        return redirect()->back()
            ->with('info', $msg);
    }

    public function desfazerPulo(Rotina $rotina, Request $request)
    {
        $data = $request->input('data');

        if ($rotina->foiPuladaHoje($data)) {
            $rotina->desfazerPuloHoje($data);

            $msg = 'Pulo desfeito';
            if ($data) {
                $msg .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
            } else {
                $msg .= ' para hoje';
            }
            $msg .= '! A rotina voltou a aparecer como pendente.';

            return redirect()->back()
                ->with('success', $msg);
        }

        return redirect()->back()
            ->with('warning', 'Esta rotina não foi pulada nesta data.');
    }

    public function calendario(Request $request, $visualizacao = 'mes')
    {
        $data = $request->input('data') ? \Carbon\Carbon::parse($request->input('data')) : now();
        $visualizacao = in_array($visualizacao, ['dia', 'semana', 'mes']) ? $visualizacao : 'mes';

        $userId = auth()->id();
        $rotinas = Rotina::ativas()->doUsuario($userId)->get();
        $medicamentosPorDia = RegistroMedicamento::where('user_id', $userId)
            ->select('data')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('data')
            ->pluck('total', 'data')
            ->toArray();

        return view('Alfred::rotinas.calendario', compact(
            'rotinas',
            'data',
            'visualizacao',
            'medicamentosPorDia'
        ));
    }
}

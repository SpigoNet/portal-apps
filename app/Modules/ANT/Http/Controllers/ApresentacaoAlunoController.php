<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntApresentacao;
use App\Modules\ANT\Models\AntApresentacaoApresentador;
use App\Modules\ANT\Models\AntApresentacaoEntrega;
use App\Modules\ANT\Models\AntEstrela;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Services\SemestreService;
use App\Modules\ANT\Services\TrabalhoUploadService;
use Illuminate\Http\Request;

class ApresentacaoAlunoController extends Controller
{
    private function alunoLogado(): AntAluno
    {
        return AntAluno::where('user_id', auth()->id())->firstOrFail();
    }

    // Lista as apresentações em que o aluno é apresentador
    public function index(Request $request)
    {
        $aluno = $this->alunoLogado();
        $materiaId = $request->query('materia_id');

        $apresentadores = AntApresentacaoApresentador::with([
            'agendamento.apresentacao.materia',
            'agendamento.apresentacao.pesoApresentacao',
            'entrega',
        ])
            ->where('aluno_ra', $aluno->ra)
            ->when($materiaId, function ($q) use ($materiaId) {
                $q->whereHas('agendamento.apresentacao', function ($q2) use ($materiaId) {
                    $q2->where('materia_id', $materiaId);
                });
            })
            ->get()
            ->sortBy(fn ($a) => $a->agendamento->data);

        $materiaFiltro = $materiaId ? AntMateria::find($materiaId) : null;

        return view('ANT::aluno.apresentacoes.index', compact('apresentadores', 'aluno', 'materiaFiltro'));
    }

    // Ver detalhes de uma apresentação atribuída ao aluno
    public function show($agendamentoId)
    {
        $aluno = $this->alunoLogado();

        $apresentador = AntApresentacaoApresentador::with([
            'agendamento.apresentacao.materia',
            'agendamento.apresentacao.pesoApresentacao',
            'entrega',
        ])
            ->where('agendamento_id', $agendamentoId)
            ->where('aluno_ra', $aluno->ra)
            ->firstOrFail();

        return view('ANT::aluno.apresentacoes.show', compact('apresentador', 'aluno'));
    }

    // Enviar a entrega (material da apresentação)
    public function entregar(Request $request, $agendamentoId)
    {
        $aluno = $this->alunoLogado();

        $apresentador = AntApresentacaoApresentador::with(['agendamento.apresentacao.materia'])
            ->where('agendamento_id', $agendamentoId)
            ->where('aluno_ra', $aluno->ra)
            ->firstOrFail();

        $apresentacao = $apresentador->agendamento->apresentacao;

        $request->validate([
            'comentario_aluno' => 'nullable|string',
            'arquivos.*' => 'nullable|file|max:102400',
            'link' => 'nullable|url',
        ]);

        $caminhos = [];

        if ($request->filled('link')) {
            $caminhos[] = $request->link;
        }

        if ($request->hasFile('arquivos')) {
            $targetPath = "ant/apresentacoes/{$apresentacao->semestre}/{$apresentacao->materia->nome_curto}/{$apresentacao->id}/{$aluno->ra}";

            foreach ($request->file('arquivos') as $arquivo) {
                try {
                    $path = TrabalhoUploadService::uploadArquivo($arquivo, $targetPath);
                    $caminhos[] = $path;
                } catch (\Exception $e) {
                    return back()->withErrors(['arquivos' => 'Erro ao enviar arquivo: '.$e->getMessage()])->withInput();
                }
            }
        }

        if (empty($caminhos)) {
            return back()->withErrors(['arquivos' => 'Envie ao menos um arquivo ou link.']);
        }

        AntApresentacaoEntrega::updateOrCreate(
            ['apresentador_id' => $apresentador->id],
            [
                'arquivos' => json_encode($caminhos),
                'comentario_aluno' => $request->comentario_aluno,
                'data_entrega' => now(),
            ]
        );

        return redirect()->route('ant.apresentacoes.aluno.show', $agendamentoId)
            ->with('success', 'Entrega da apresentação enviada com sucesso!');
    }

    // Lista as matérias do aluno que possuem apresentações (para ver o rank)
    public function rankIndex()
    {
        $aluno = $this->alunoLogado();
        $semestre = SemestreService::getCurrent();

        $materiaIds = $aluno->materias()->pluck('ant_materias.id');

        $apresentacoes = AntApresentacao::whereIn('materia_id', $materiaIds)
            ->where('semestre', $semestre)
            ->with('materia')
            ->orderBy('nome')
            ->get();

        return view('ANT::aluno.apresentacoes.rank_index', compact('apresentacoes', 'semestre'));
    }

    // Ver o rank de estrelas de uma matéria (disponível para qualquer aluno matriculado)
    public function rank($materiaId)
    {
        $aluno = $this->alunoLogado();
        $semestre = SemestreService::getCurrent();

        $matriculado = $aluno->materias()
            ->where('ant_materias.id', $materiaId)
            ->where('ant_aluno_materia.semestre', $semestre)
            ->exists();

        if (! $matriculado) {
            abort(403, 'Você não está matriculado nesta disciplina.');
        }

        $materia = AntMateria::findOrFail($materiaId);
        $rank = AntEstrela::rankPorMateria($materiaId, $semestre);

        return view('ANT::aluno.apresentacoes.rank', compact('materia', 'semestre', 'rank'));
    }
}

<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntApresentacao;
use App\Modules\ANT\Models\AntApresentacaoAgendamento;
use App\Modules\ANT\Models\AntApresentacaoApresentador;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntEstrela;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntPeso;
use App\Modules\ANT\Models\AntTipoTrabalho;
use App\Modules\ANT\Models\AntTrabalho;
use App\Modules\ANT\Services\SemestreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfessorController extends Controller
{
    public function setSemestre(Request $request, string $semestre)
    {
        $available = SemestreService::getAvailable();
        if (! in_array($semestre, $available)) {
            abort(404, 'Semestre não encontrado.');
        }

        session(['ant_semestre_professor' => $semestre]);

        return redirect()->route('ant.professor.index')
            ->with('success', "Visualizando semestre {$semestre}.");
    }

    // Dashboard do Professor
    public function index()
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);
        $config = AntConfiguracao::first();
        $isAdmin = $config ? $config->isAdmin($user->email) : false;

        // Verifica se é professor de fato
        $isProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (! $isProfessor && ! $isAdmin) {
            return redirect()->route('ant.home');
        }

        // Busca matérias e contadores
        $materiasProfessor = AntMateria::whereHas('professores', function ($q) use ($user, $semestreAtual) {
            $q->where('user_id', $user->id)->where('semestre', $semestreAtual);
        })
            ->with(['trabalhos' => function ($q) use ($semestreAtual) {
                $q->where('semestre', $semestreAtual)
                    ->withCount(['entregas as pendentes_count' => function ($query) {
                        $query->whereNull('nota');
                    }]);
            }])
            ->get();

        return view('ANT::professores.index', compact('materiasProfessor', 'semestreAtual', 'user', 'isAdmin'));
    }

    // Lista de Entregas de um Trabalho Específico
    public function trabalho($id)
    {
        $user = auth()->user();

        // Busca o trabalho e garante que o professor tem acesso a essa matéria
        $trabalho = AntTrabalho::with(['materia', 'tipoTrabalho', 'prova'])->findOrFail($id);

        // Segurança: Verifica se o user é professor desta matéria
        $ehProfessorDestaMateria = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $trabalho->materia_id)
            ->exists();

        // (Opcional: Permitir se for Admin também)

        if (! $ehProfessorDestaMateria) {
            abort(403, 'Acesso negado a esta disciplina.');
        }

        // Busca todos os alunos matriculados nesta matéria para montar a lista completa
        // Mesmo quem não entregou deve aparecer na lista
        $alunos = AntAluno::whereHas('materias', function ($q) use ($trabalho) {
            $q->where('ant_materias.id', $trabalho->materia_id)
                ->where('ant_aluno_materia.semestre', $trabalho->semestre);
        })
            ->with(['entregas' => function ($q) use ($trabalho) {
                $q->where('trabalho_id', $trabalho->id);
            }])
            // Se for prova, carregamos a resposta para ver a nota automática
            ->with(['user']) // Carrega dados do user se precisar de foto/email
            ->orderBy('nome')
            ->get();

        // Estatísticas para o topo da página
        $totalAlunos = $alunos->count();
        $entregues = 0;
        $corrigidos = 0;

        foreach ($alunos as $aluno) {
            $entrega = $aluno->entregas->first();
            if ($entrega) {
                $entregues++;
                if ($entrega->nota !== null) {
                    $corrigidos++;
                }
            }
        }

        return view('ANT::professores.trabalho', compact('trabalho', 'alunos', 'totalAlunos', 'entregues', 'corrigidos'));
    }

    public function boletim($idMateria)
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        $materia = AntMateria::findOrFail($idMateria);

        // Segurança: Verifica se o user é professor desta matéria neste semestre
        $ehProfessorDestaMateria = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (! $ehProfessorDestaMateria) {
            abort(403, 'Acesso negado a esta disciplina.');
        }

        // 1. Pesos (Grupos de Notas) definidos para a matéria
        $pesos = AntPeso::where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->get();

        $pesosGrupos = $pesos->keyBy('id'); // Indexa por peso_id para acesso rápido
        $gruposNome = $pesos->pluck('grupo', 'id'); // Nomes dos grupos

        // 2. Trabalhos e Notas vinculadas aos pesos
        $trabalhos = AntTrabalho::where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->whereNotNull('peso_id') // Apenas trabalhos que valem nota
            ->with(['entregas' => function ($q) {
                // Seleciona apenas as entregas que têm nota atribuída
                $q->whereNotNull('nota')->select('trabalho_id', 'aluno_ra', 'nota');
            }])
            ->get();

        // 3. Alunos matriculados
        $alunos = AntAluno::whereHas('materias', function ($q) use ($idMateria, $semestreAtual) {
            $q->where('ant_materias.id', $idMateria)
                ->where('ant_aluno_materia.semestre', $semestreAtual);
        })
            ->orderBy('nome')
            ->get();

        // 3.5 Apresentações (avaliação individual + participação/estrelas)
        $apresentacoes = AntApresentacao::where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->get();

        $aprPorPesoApresentacao = $apresentacoes->whereNotNull('peso_apresentacao_id')
            ->keyBy('peso_apresentacao_id');
        $aprPorPesoParticipacao = $apresentacoes->whereNotNull('peso_participacao_id')
            ->keyBy('peso_participacao_id');

        $agendamentoIds = AntApresentacaoAgendamento::whereIn('apresentacao_id', $apresentacoes->pluck('id'))->pluck('id');

        $notasApresentacaoByAluno = AntApresentacaoApresentador::whereIn('agendamento_id', $agendamentoIds)
            ->whereNotNull('nota')
            ->get()
            ->groupBy('aluno_ra')
            ->map(fn ($itens) => $itens->avg('nota'));

        $estrelasByAluno = AntEstrela::where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->get()
            ->groupBy('aluno_ra')
            ->map->count();
        $maxEstrelas = $estrelasByAluno->max() ?? 0;

        // 4. Cálculo da Média Final Ponderada por aluno
        $dadosBoletim = [];
        $pesoTotal = $pesos->sum('valor'); // Total teórico (Ex: 10 ou 100)

        foreach ($alunos as $aluno) {
            $alunoRa = $aluno->ra;
            $notaPonderadaTotal = 0;
            $notasPorGrupo = [];

            foreach ($pesos as $peso) {
                $pesoId = $peso->id;
                $valorPeso = $peso->valor;
                $totalNotas = 0;
                $mediaGrupo = 0;
                $notaPonderada = 0;
                $extra = '';

                if (isset($aprPorPesoApresentacao[$pesoId])) {
                    // Nota da avaliação da apresentação (média das notas individuais 0-10)
                    if ($notasApresentacaoByAluno->has($alunoRa)) {
                        $mediaGrupo = (float) $notasApresentacaoByAluno->get($alunoRa);
                        $notaPonderada = ($mediaGrupo / 10.0) * $valorPeso;
                        $totalNotas = 1;
                        $extra = 'Avaliação da apresentação';
                    }
                } elseif (isset($aprPorPesoParticipacao[$pesoId])) {
                    // Participação: normalizado pelo máximo de estrelas da turma
                    $estrelasAluno = $estrelasByAluno->get($alunoRa, 0);
                    if ($maxEstrelas > 0) {
                        $mediaGrupo = ($estrelasAluno / $maxEstrelas) * 10;
                        $notaPonderada = ($estrelasAluno / $maxEstrelas) * $valorPeso;
                    }
                    $totalNotas = $estrelasAluno > 0 ? 1 : 0;
                    $extra = "{$estrelasAluno} estrela(s) / máx {$maxEstrelas}";
                } else {
                    // Trabalho tradicional
                    $somaNotas = 0;
                    $cnt = 0;
                    foreach ($trabalhos as $trabalho) {
                        if ($trabalho->peso_id != $pesoId) {
                            continue;
                        }
                        $entrega = $trabalho->entregas->where('aluno_ra', $alunoRa)->first();
                        if ($entrega && $entrega->nota !== null) {
                            $somaNotas += $entrega->nota;
                            $cnt++;
                        }
                    }
                    if ($cnt > 0) {
                        $mediaGrupo = $somaNotas / $cnt;
                        $notaPonderada = ($mediaGrupo / 10.0) * $valorPeso;
                    }
                    $totalNotas = $cnt;
                }

                $notasPorGrupo[$pesoId] = [
                    'totalNotas' => $totalNotas,
                    'somaNotas' => 0,
                    'mediaGrupo' => $mediaGrupo,
                    'notaPonderada' => $notaPonderada,
                    'extra' => $extra,
                ];
                $notaPonderadaTotal += $notaPonderada;
            }

            $dadosBoletim[] = [
                'aluno' => $aluno,
                'ra' => $alunoRa,
                'notasGrupos' => $notasPorGrupo,
                'notaFinal' => $notaPonderadaTotal,
            ];
        }

        return view('ANT::professores.boletim', compact('materia', 'semestreAtual', 'gruposNome', 'dadosBoletim', 'pesoTotal'));
    }

    // Formulário de Editar Trabalho
    public function edit($id)
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        $trabalho = AntTrabalho::findOrFail($id);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $trabalho->materia_id)
            ->exists();

        if (! $ehProfessor) {
            abort(403, 'Você não tem permissão para editar trabalhos desta disciplina.');
        }

        $materias = AntMateria::whereHas('professores', function ($q) use ($user, $semestreAtual) {
            $q->where('user_id', $user->id)->where('semestre', $semestreAtual);
        })->get();

        $tipos = AntTipoTrabalho::all();

        $pesos = AntPeso::whereIn('materia_id', $materias->pluck('id'))
            ->where('semestre', $semestreAtual)
            ->with('materia')
            ->get();

        return view('ANT::professores.edit', compact('trabalho', 'materias', 'tipos', 'pesos', 'semestreAtual'));
    }

    // Salvar Alterações do Trabalho
    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'materia_id' => 'required|exists:ant_materias,id',
            'tipos_ids' => 'required|array|min:1',
            'tipos_ids.*' => 'exists:ant_tipos_trabalho,id',
            'peso_id' => 'required|exists:ant_pesos,id',
            'prazo' => 'required|date',
            'maximo_alunos' => 'required|integer|min:1',
            'descricao' => 'required|string',
            'dicas_correcao' => 'nullable|string',
        ]);

        $trabalho = AntTrabalho::findOrFail($id);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', auth()->id())
            ->where('materia_id', $trabalho->materia_id)
            ->exists();

        if (! $ehProfessor) {
            abort(403, 'Você não tem permissão para editar trabalhos desta disciplina.');
        }

        // Se a matéria foi alterada, verificar que o professor também leciona a nova matéria
        if ($request->materia_id != $trabalho->materia_id) {
            $ehProfessorNovaMateria = DB::table('ant_professor_materia')
                ->where('user_id', auth()->id())
                ->where('materia_id', $request->materia_id)
                ->exists();

            if (! $ehProfessorNovaMateria) {
                abort(403, 'Você não tem permissão para mover este trabalho para a disciplina selecionada.');
            }
        }

        $tiposIds = array_map('intval', $request->tipos_ids);

        $trabalho->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'dicas_correcao' => $request->dicas_correcao,
            'materia_id' => $request->materia_id,
            'tipo_trabalho_id' => $tiposIds[0],
            'tipos_trabalho_ids' => $tiposIds,
            'prazo' => $request->prazo,
            'maximo_alunos' => $request->maximo_alunos,
            'peso_id' => $request->peso_id,
        ]);

        return redirect()->route('ant.professor.trabalho', $trabalho->id)->with('success', 'Trabalho atualizado com sucesso!');
    }

    // Alterar apenas a data de entrega (prazo) do trabalho
    public function updatePrazo(Request $request, $id)
    {
        $trabalho = AntTrabalho::findOrFail($id);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', auth()->id())
            ->where('materia_id', $trabalho->materia_id)
            ->exists();

        if (! $ehProfessor) {
            abort(403, 'Você não tem permissão para editar este trabalho.');
        }

        $request->validate([
            'prazo' => 'required|date',
        ]);

        $trabalho->update(['prazo' => $request->prazo]);

        return redirect()->route('ant.professor.trabalho', $trabalho->id)
            ->with('success', 'Data de entrega atualizada com sucesso!');
    }

    // Formulário de Novo Trabalho
    public function create()
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        // 1. Busca as Matérias que o professor leciona neste semestre
        // Precisamos delas para o Select
        $materias = AntMateria::whereHas('professores', function ($q) use ($user, $semestreAtual) {
            $q->where('user_id', $user->id)->where('semestre', $semestreAtual);
        })->get();

        if ($materias->isEmpty()) {
            return redirect()->route('ant.professor.index')->with('error', 'Você não está vinculado a nenhuma matéria neste semestre.');
        }

        // 2. Busca Tipos de Trabalho (PDF, Link, ZIP...)
        $tipos = AntTipoTrabalho::all();

        // 3. Busca os Pesos disponíveis para essas matérias no semestre atual
        // Ex: P1 da Matéria X, Trabalho da Matéria Y
        $pesos = AntPeso::whereIn('materia_id', $materias->pluck('id'))
            ->where('semestre', $semestreAtual)
            ->with('materia') // Para exibir o nome da matéria no select
            ->get();

        return view('ANT::professores.create', compact('materias', 'tipos', 'pesos', 'semestreAtual'));
    }

    // Salvar Novo Trabalho
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'materia_id' => 'required|exists:ant_materias,id',
            'tipos_ids' => 'required|array|min:1',
            'tipos_ids.*' => 'exists:ant_tipos_trabalho,id',
            'peso_id' => 'required|exists:ant_pesos,id',
            'prazo' => 'required|date',
            'maximo_alunos' => 'required|integer|min:1',
            'descricao' => 'required|string',
            'dicas_correcao' => 'nullable|string',
        ]);

        $semestreAtual = SemestreService::getCurrent();
        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', auth()->id())
            ->where('materia_id', $request->materia_id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (! $ehProfessor) {
            abort(403, 'Você não tem permissão para criar trabalhos nesta disciplina.');
        }

        $tiposIds = array_map('intval', $request->tipos_ids);

        // Criação
        AntTrabalho::create([
            'semestre' => $semestreAtual,
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'dicas_correcao' => $request->dicas_correcao,
            'materia_id' => $request->materia_id,
            'tipo_trabalho_id' => $tiposIds[0], // first selection for backward compat
            'tipos_trabalho_ids' => $tiposIds,
            'prazo' => $request->prazo,
            'maximo_alunos' => $request->maximo_alunos,
            'peso_id' => $request->peso_id,
        ]);

        return redirect()->route('ant.professor.index')->with('success', 'Trabalho criado com sucesso!');
    }
}

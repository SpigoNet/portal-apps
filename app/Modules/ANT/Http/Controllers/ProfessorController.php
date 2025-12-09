<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntTrabalho;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntTipoTrabalho;
use App\Modules\ANT\Models\AntPeso;
use Illuminate\Support\Facades\DB;

class ProfessorController extends Controller
{
    // Dashboard do Professor
    public function index()
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');
        $isAdmin = $config ? $config->isAdmin($user->email) : false;

        // Verifica se é professor de fato
        $isProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$isProfessor && !$isAdmin) {
            return redirect()->route('ant.home');
        }

        // Busca matérias e contadores
        $materiasProfessor = AntMateria::whereHas('professores', function($q) use ($user, $semestreAtual) {
            $q->where('user_id', $user->id)->where('semestre', $semestreAtual);
        })
            ->with(['trabalhos' => function($q) use ($semestreAtual) {
                $q->where('semestre', $semestreAtual)
                    ->withCount(['entregas as pendentes_count' => function($query) {
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

        if (!$ehProfessorDestaMateria) {
            abort(403, 'Acesso negado a esta disciplina.');
        }

        // Busca todos os alunos matriculados nesta matéria para montar a lista completa
        // Mesmo quem não entregou deve aparecer na lista
        $alunos = AntAluno::whereHas('materias', function($q) use ($trabalho) {
            $q->where('ant_materias.id', $trabalho->materia_id)
                ->where('ant_aluno_materia.semestre', $trabalho->semestre);
        })
            ->with(['entregas' => function($q) use ($trabalho) {
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

        foreach($alunos as $aluno) {
            $entrega = $aluno->entregas->first();
            if ($entrega) {
                $entregues++;
                if ($entrega->nota !== null) $corrigidos++;
            }
        }

        return view('ANT::professores.trabalho', compact('trabalho', 'alunos', 'totalAlunos', 'entregues', 'corrigidos'));
    }

    public function boletim($idMateria)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $materia = AntMateria::findOrFail($idMateria);

        // Segurança: Verifica se o user é professor desta matéria neste semestre
        $ehProfessorDestaMateria = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessorDestaMateria) {
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
            ->with(['entregas' => function($q) {
                // Seleciona apenas as entregas que têm nota atribuída
                $q->whereNotNull('nota')->select('trabalho_id', 'aluno_ra', 'nota');
            }])
            ->get();

        // 3. Alunos matriculados
        $alunos = AntAluno::whereHas('materias', function($q) use ($idMateria, $semestreAtual) {
            $q->where('ant_materias.id', $idMateria)
                ->where('ant_aluno_materia.semestre', $semestreAtual);
        })
            ->orderBy('nome')
            ->get();

        // 4. Cálculo da Média Final Ponderada por aluno
        $dadosBoletim = [];
        $pesoTotal = $pesos->sum('valor'); // Total teórico (Ex: 10 ou 100)

        foreach ($alunos as $aluno) {
            $notasPorGrupo = $gruposNome->mapWithKeys(function ($nome, $pesoId) {
                // Inicializa estrutura para cada grupo de peso
                return [$pesoId => ['totalNotas' => 0, 'somaNotas' => 0, 'mediaGrupo' => 0, 'notaPonderada' => 0]];
            })->toArray();

            $alunoRa = $aluno->ra;
            $notaPonderadaTotal = 0;

            // A. Coletar Notas dos Trabalhos e somar dentro de seus grupos
            foreach ($trabalhos as $trabalho) {
                $pesoId = $trabalho->peso_id;

                if (isset($notasPorGrupo[$pesoId])) {
                    // Busca a entrega do aluno para este trabalho
                    $entrega = $trabalho->entregas->where('aluno_ra', $alunoRa)->first();

                    if ($entrega && $entrega->nota !== null) {
                        // Soma todas as notas (0-10) que compõem este grupo
                        $notasPorGrupo[$pesoId]['somaNotas'] += $entrega->nota;
                        $notasPorGrupo[$pesoId]['totalNotas']++;
                    }
                }
            }

            // B. Calcular Média Ponderada
            foreach ($pesos as $peso) {
                $pesoId = $peso->id;
                $valorPeso = $peso->valor; // Valor total do grupo (Ex: 10.0)
                $dadosGrupo = $notasPorGrupo[$pesoId];

                $mediaGrupo = 0;
                if ($dadosGrupo['totalNotas'] > 0) {
                    // Média aritmética das notas (0-10) de todos os trabalhos do grupo
                    $mediaGrupo = $dadosGrupo['somaNotas'] / $dadosGrupo['totalNotas'];
                }

                // Normaliza e pondera: (Média do Grupo / 10) * Valor do Peso do Grupo
                $notaPonderada = ($mediaGrupo / 10.0) * $valorPeso;

                $notasPorGrupo[$pesoId]['mediaGrupo'] = $mediaGrupo;
                $notasPorGrupo[$pesoId]['notaPonderada'] = $notaPonderada;
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
    // Formulário de Novo Trabalho
    public function create()
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        // 1. Busca as Matérias que o professor leciona neste semestre
        // Precisamos delas para o Select
        $materias = AntMateria::whereHas('professores', function($q) use ($user, $semestreAtual) {
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
            'tipo_trabalho_id' => 'required|exists:ant_tipos_trabalho,id',
            'peso_id' => 'required|exists:ant_pesos,id',
            'prazo' => 'required|date',
            'maximo_alunos' => 'required|integer|min:1',
            'descricao' => 'required|string',
            'dicas_correcao' => 'nullable|string', // Campo novo da IA
        ]);

        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        // Segurança: Verificar se o professor realmente dá aula dessa matéria
        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', auth()->id())
            ->where('materia_id', $request->materia_id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Você não tem permissão para criar trabalhos nesta disciplina.');
        }

        // Criação
        AntTrabalho::create([
            'semestre' => $semestreAtual,
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'dicas_correcao' => $request->dicas_correcao,
            'materia_id' => $request->materia_id,
            'tipo_trabalho_id' => $request->tipo_trabalho_id,
            'prazo' => $request->prazo,
            'maximo_alunos' => $request->maximo_alunos,
            'peso_id' => $request->peso_id,
        ]);

        return redirect()->route('ant.professor.index')->with('success', 'Trabalho criado com sucesso!');
    }
}

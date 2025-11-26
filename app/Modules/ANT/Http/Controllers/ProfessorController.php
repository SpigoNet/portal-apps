<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntTrabalho;
use App\Modules\ANT\Models\AntAluno;
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
}

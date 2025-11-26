<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntMateria;

class AntHomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Carregar Configurações (Semestre e Admins)
        $config = AntConfiguracao::first();

        // Fallback se não houver config criada ainda
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        // Verifica se é Admin usando o método do Model ou fallback vazio
        $isAdmin = $config ? $config->isAdmin($user->email) : false;

        // ---------------------------------------------------------
        // A. VERIFICAÇÃO DE PROFESSOR
        // ---------------------------------------------------------
        $isProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('semestre', $semestreAtual)
            ->exists();

        // Se for Professor (mesmo que também seja Admin), mostramos o painel de Professor
        // Passamos a flag $isAdmin para a view exibir o botão de acesso administrativo
        if ($isProfessor) {
            $materiasProfessor = AntMateria::whereHas('professores', function($q) use ($user, $semestreAtual) {
                $q->where('user_id', $user->id)
                    ->where('semestre', $semestreAtual);
            })
                ->with(['trabalhos' => function($q) use ($semestreAtual) {
                    $q->where('semestre', $semestreAtual)
                        ->withCount(['entregas as pendentes_count' => function($query) {
                            $query->whereNull('nota');
                        }]);
                }])
                ->get();

            // Retorna view de professor com a flag isAdmin
            return view('ANT::professores.index', compact('materiasProfessor', 'semestreAtual', 'user', 'isAdmin'));
        }

        // ---------------------------------------------------------
        // B. APENAS ADMIN (Não é professor no semestre)
        // ---------------------------------------------------------
        if ($isAdmin) {
            return view('ANT::admin.dashboard', compact('semestreAtual'));
        }

        // ---------------------------------------------------------
        // C. ALUNO (Padrão)
        // ---------------------------------------------------------
        $aluno = AntAluno::where('user_id', $user->id)->first();

        if (!$aluno) {
            return redirect()->route('ant.vincular_ra');
        }

        $materias = $aluno->materias()
            ->wherePivot('semestre', $semestreAtual)
            ->with(['trabalhos' => function($query) use ($semestreAtual, $aluno) {
                $query->where('semestre', $semestreAtual)
                    ->with(['tipoTrabalho', 'prova'])
                    ->with(['entregas' => function($q) use ($aluno) {
                        $q->where('aluno_ra', $aluno->ra);
                    }])
                    ->orderBy('prazo', 'asc');
            }])
            ->get();

        return view('ANT::index', compact('aluno', 'materias', 'semestreAtual'));
    }

    public function vincularRaView()
    {
        return view('ANT::vincular_ra');
    }

    public function vincularRaStore(Request $request)
    {
        $request->validate(['ra' => 'required|string']);

        // Busca o aluno pelo RA informado
        $aluno = AntAluno::where('ra', $request->ra)->first();

        if (!$aluno) {
            return back()->withErrors(['ra' => 'RA não encontrado na base de dados.']);
        }

        if ($aluno->user_id) {
            return back()->withErrors(['ra' => 'Este RA já pertence a outro usuário.']);
        }

        // Realiza o vínculo do usuário atual com o registro de aluno
        $aluno->user_id = auth()->id();
        $aluno->save();

        return redirect()->route('ant.home')->with('success', 'RA vinculado com sucesso!');
    }
}

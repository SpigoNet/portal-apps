<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntConfiguracao;

class AdminProfessorController extends Controller
{
    public function index()
    {
        // Busca todos os vínculos com join para pegar os nomes
        // Ordena por Semestre (mais recente primeiro) e depois Nome do Professor
        $vinculos = DB::table('ant_professor_materia')
            ->join('users', 'ant_professor_materia.user_id', '=', 'users.id')
            ->join('ant_materias', 'ant_professor_materia.materia_id', '=', 'ant_materias.id')
            ->select(
                'ant_professor_materia.id',
                'users.name as professor_nome',
                'users.email as professor_email',
                'ant_materias.nome as materia_nome',
                'ant_materias.nome_curto',
                'ant_professor_materia.semestre'
            )
            ->orderBy('ant_professor_materia.semestre', 'desc')
            ->orderBy('users.name')
            ->get();

        return view('ANT::admin.professores.index', compact('vinculos'));
    }

    public function create()
    {
        // Carrega usuários para o select (Idealmente filtrar, mas vamos pegar todos por enquanto)
        $users = User::orderBy('name')->get();
        $materias = AntMateria::orderBy('nome')->get();

        // Sugere o semestre atual
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        return view('ANT::admin.professores.create', compact('users', 'materias', 'semestreAtual'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'materia_id' => 'required|exists:ant_materias,id',
            'semestre' => 'required|string|max:6', // Ex: 2025-2
        ]);

        // Evita duplicidade (O mesmo professor na mesma matéria no mesmo semestre)
        $existe = DB::table('ant_professor_materia')
            ->where('user_id', $request->user_id)
            ->where('materia_id', $request->materia_id)
            ->where('semestre', $request->semestre)
            ->exists();

        if ($existe) {
            return back()->withErrors(['erro' => 'Este professor já está vinculado a esta matéria neste semestre.']);
        }

        // Cria o vínculo
        // Usamos o DB direto pois não criamos um Model específico para a tabela Pivot
        DB::table('ant_professor_materia')->insert([
            'user_id' => $request->user_id,
            'materia_id' => $request->materia_id,
            'semestre' => $request->semestre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ant.admin.professores.index')
            ->with('success', 'Professor vinculado com sucesso!');
    }

    public function destroy($id)
    {
        DB::table('ant_professor_materia')->where('id', $id)->delete();
        return back()->with('success', 'Vínculo removido.');
    }
}

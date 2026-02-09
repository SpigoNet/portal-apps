<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntPeso;

class PesoController extends Controller
{
    /**
     * Exibe formulário e lista de pesos atuais
     */
    public function create()
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');
        $isAdmin = $config ? $config->isAdmin($user->email) : false;

        // 1. Busca Matérias
        if ($isAdmin) {
            // Admin vê todas as matérias
            $materias = AntMateria::orderBy('nome')->get();
        } else {
            // Professor vê apenas as suas
            $materias = AntMateria::whereHas('professores', function ($q) use ($user, $semestreAtual) {
                $q->where('user_id', $user->id)->where('semestre', $semestreAtual);
            })->get();
        }

        if ($materias->isEmpty()) {
            return redirect()->route('ant.professor.index')
                ->with('error', 'Você não está vinculado a nenhuma matéria para definir pesos.');
        }

        // 2. Busca Pesos JÁ CADASTRADOS para essas matérias (Para exibição de lista)
        $pesosExistentes = AntPeso::whereIn('materia_id', $materias->pluck('id'))
            ->where('semestre', $semestreAtual)
            ->with('materia')
            ->orderBy('materia_id')
            ->get()
            ->groupBy('materia_id'); // Agrupa por matéria para facilitar na view

        return view('ANT::pesos.create', compact('materias', 'pesosExistentes', 'semestreAtual'));
    }

    /**
     * Salva o novo grupo de notas
     */
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:ant_materias,id',
            'grupo' => 'required|string|max:100', // Ex: "P1", "Trabalhos"
            'valor' => 'required|numeric|min:0|max:100', // Valor total desse grupo (ex: 10.0)
        ]);

        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');
        $isAdmin = $config ? $config->isAdmin(auth()->user()->email) : false;

        if (!$isAdmin) {
            // Segurança: Verifica se é professor da matéria
            $ehProfessor = DB::table('ant_professor_materia')
                ->where('user_id', auth()->id())
                ->where('materia_id', $request->materia_id)
                ->where('semestre', $semestreAtual)
                ->exists();

            if (!$ehProfessor) {
                abort(403, 'Acesso negado a esta disciplina.');
            }
        }

        // Evitar duplicidade de nome na mesma matéria/semestre
        // Ex: Não criar dois grupos chamados "P1" na mesma matéria
        $existe = AntPeso::where('materia_id', $request->materia_id)
            ->where('semestre', $semestreAtual)
            ->where('grupo', $request->grupo)
            ->exists();

        if ($existe) {
            return back()->withErrors(['grupo' => 'Já existe um grupo com este nome para esta disciplina.']);
        }

        AntPeso::create([
            'semestre' => $semestreAtual,
            'materia_id' => $request->materia_id,
            'grupo' => $request->grupo,
            'valor' => $request->valor
        ]);

        return redirect()->route('ant.pesos.create')
            ->with('success', "Grupo '{$request->grupo}' criado com sucesso!");
    }

    // Opcional: Método para deletar um peso (caso tenha criado errado)
    public function destroy($id)
    {
        $peso = AntPeso::findOrFail($id);

        // Verifica se tem trabalhos vinculados antes de apagar
        // Se tiver, o banco pode bloquear (Foreign Key) ou podemos avisar
        try {
            $peso->delete();
            return back()->with('success', 'Grupo de notas removido.');
        } catch (\Exception $e) {
            return back()->withErrors(['erro' => 'Não é possível remover este grupo pois já existem trabalhos vinculados a ele.']);
        }
    }
}

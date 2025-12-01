<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\ANT\Models\AntMateria;

class AdminMateriaController extends Controller
{
    public function index()
    {
        $materias = AntMateria::orderBy('nome')->get();
        return view('ANT::admin.materias.index', compact('materias'));
    }

    public function create()
    {
        return view('ANT::admin.materias.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'nome_curto' => 'required|string|max:20|unique:ant_materias,nome_curto',
        ]);

        AntMateria::create($request->all());

        return redirect()->route('ant.admin.materias.index')
            ->with('success', 'Matéria criada com sucesso!');
    }

    public function edit($id)
    {
        $materia = AntMateria::findOrFail($id);
        return view('ANT::admin.materias.form', compact('materia'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'nome_curto' => 'required|string|max:20|unique:ant_materias,nome_curto,' . $id,
        ]);

        $materia = AntMateria::findOrFail($id);
        $materia->update($request->all());

        return redirect()->route('ant.admin.materias.index')
            ->with('success', 'Matéria atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $materia = AntMateria::findOrFail($id);

        // Verifica dependências antes de excluir (opcional, mas recomendado)
        if ($materia->trabalhos()->exists() || $materia->alunos()->exists()) {
            return back()->with('error', 'Não é possível excluir esta matéria pois ela já possui vínculos (trabalhos ou alunos).');
        }

        $materia->delete();

        return back()->with('success', 'Matéria removida.');
    }
}

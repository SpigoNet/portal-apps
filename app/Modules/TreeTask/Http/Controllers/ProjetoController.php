<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Projeto;
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    public function index()
    {
        // Busca projetos onde o usuário é dono (exemplo)
        $projetos = Projeto::with('owner')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('TreeTask::index', compact('projetos'));
    }

    public function create()
    {
        return view('TreeTask::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'required|string',
            'data_inicio' => 'nullable|date',
            'data_prevista_termino' => 'nullable|date|after_or_equal:data_inicio',
        ]);

        // Define o dono como o usuário logado e status padrão
        $data = array_merge($validated, [
            'id_user_owner' => auth()->id(),
            'status' => 'Planejamento'
        ]);

        Projeto::create($data);

        return redirect()->route('treetask.index')
            ->with('success', 'Projeto criado com sucesso.');
    }

    public function show($id)
    {
        // Carrega projeto, fases (ordenadas) e tarefas de cada fase
        $projeto = Projeto::with(['fases' => function($query) {
            $query->orderBy('ordem', 'asc');
        }, 'fases.tarefas.responsavel'])
            ->findOrFail($id);

        return view('TreeTask::show', compact('projeto'));
    }
}

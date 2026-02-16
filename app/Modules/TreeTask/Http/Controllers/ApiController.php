<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Anexo;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    private $statusList = 'A Fazer,Em Andamento,Concluído,Planejamento,Aguardando resposta';

    // ==================== PROJETOS ====================

    /**
     * Listar todos os projetos
     */
    public function projetosIndex()
    {
        $projetos = Projeto::with('owner')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projetos,
        ]);
    }

    /**
     * Obter detalhes de um projeto
     */
    public function projetosShow($id)
    {
        $projeto = Projeto::with(['fases' => function ($q) {
            $q->orderBy('ordem', 'asc');
        }, 'fases.tarefas' => function ($q) {
            $q->orderBy('ordem', 'asc');
        }, 'fases.tarefas.responsavel'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $projeto,
        ]);
    }

    /**
     * Criar novo projeto
     */
    public function projetosStore(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'required|string',
            'data_inicio' => 'nullable|date',
            'data_prevista_termino' => 'nullable|date|after_or_equal:data_inicio',
        ]);

        $data = array_merge($validated, [
            'id_user_owner' => auth()->user()->id,
            'status' => 'Planejamento',
        ]);

        $projeto = Projeto::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Projeto criado com sucesso.',
            'data' => $projeto,
        ], 201);
    }

    /**
     * Atualizar projeto
     */
    public function projetosUpdate(Request $request, $id)
    {
        $projeto = Projeto::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'sometimes|required|string',
            'status' => 'sometimes|required|string',
            'data_inicio' => 'nullable|date',
            'data_prevista_termino' => 'nullable|date|after_or_equal:data_inicio',
            'data_conclusao_real' => 'nullable|date',
        ]);

        $projeto->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Projeto atualizado com sucesso.',
            'data' => $projeto,
        ]);
    }

    /**
     * Excluir projeto
     */
    public function projetosDestroy($id)
    {
        $projeto = Projeto::findOrFail($id);
        $projeto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projeto excluído com sucesso.',
        ]);
    }

    // ==================== FASES ====================

    /**
     * Listar todas as fases de um projeto
     */
    public function fasesIndex($id_projeto)
    {
        $fases = Fase::where('id_projeto', $id_projeto)
            ->with('tarefas')
            ->orderBy('ordem', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fases,
        ]);
    }

    /**
     * Obter detalhes de uma fase
     */
    public function fasesShow($id)
    {
        $fase = Fase::with(['projeto', 'tarefas'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fase,
        ]);
    }

    /**
     * Criar nova fase
     */
    public function fasesStore(Request $request)
    {
        $validated = $request->validate([
            'id_projeto' => 'required|exists:treetask_projetos,id_projeto',
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);

        $ultimaOrdem = Fase::where('id_projeto', $validated['id_projeto'])->max('ordem');
        $validated['ordem'] = $ultimaOrdem ? $ultimaOrdem + 1 : 0;

        $fase = Fase::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fase criada com sucesso.',
            'data' => $fase,
        ], 201);
    }

    /**
     * Atualizar fase
     */
    public function fasesUpdate(Request $request, $id)
    {
        $fase = Fase::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'status' => 'sometimes|required|string',
            'ordem' => 'sometimes|required|integer',
        ]);

        $fase->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fase atualizada com sucesso.',
            'data' => $fase,
        ]);
    }

    /**
     * Excluir fase
     */
    public function fasesDestroy($id)
    {
        $fase = Fase::findOrFail($id);
        $fase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fase excluída com sucesso.',
        ]);
    }

    // ==================== TAREFAS ====================

    /**
     * Listar todas as tarefas de uma fase
     */
    public function tarefasIndex($id_fase)
    {
        $tarefas = Tarefa::where('id_fase', $id_fase)
            ->with(['responsavel', 'anexos'])
            ->orderBy('ordem', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tarefas,
        ]);
    }

    /**
     * Obter detalhes de uma tarefa
     */
    public function tarefasShow($id)
    {
        $tarefa = Tarefa::with(['fase.projeto', 'responsavel', 'anexos'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tarefa,
        ]);
    }

    /**
     * Criar nova tarefa
     */
    public function tarefasStore(Request $request)
    {
        $validated = $request->validate([
            'id_fase' => 'required|exists:treetask_fases,id_fase',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'id_user_responsavel' => 'required|exists:users,id',
            'prioridade' => 'nullable|string|in:Baixa,Média,Alta,Urgente',
            'data_vencimento' => 'nullable|date',
            'estimativa_tempo' => 'nullable|numeric',
        ]);

        $tarefa = Tarefa::create($validated);
        $tarefa->load(['responsavel', 'anexos']);

        return response()->json([
            'success' => true,
            'message' => 'Tarefa criada com sucesso.',
            'data' => $tarefa,
        ], 201);
    }

    /**
     * Atualizar tarefa
     */
    public function tarefasUpdate(Request $request, $id)
    {
        $tarefa = Tarefa::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'id_fase' => 'sometimes|required|exists:treetask_fases,id_fase',
            'id_user_responsavel' => 'sometimes|required|exists:users,id',
            'prioridade' => 'nullable|string|in:Baixa,Média,Alta,Urgente',
            'status' => 'sometimes|required|string|in:'.$this->statusList,
            'data_vencimento' => 'nullable|date',
            'estimativa_tempo' => 'nullable|numeric',
        ]);

        // Verifica se a fase pertence ao mesmo projeto (segurança)
        if (isset($validated['id_fase'])) {
            $novaFase = Fase::findOrFail($validated['id_fase']);
            if ($novaFase->id_projeto != $tarefa->fase->id_projeto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível mover a tarefa para uma fase de outro projeto.',
                ], 403);
            }
        }

        $tarefa->update($validated);
        $tarefa->load(['responsavel', 'anexos']);

        return response()->json([
            'success' => true,
            'message' => 'Tarefa atualizada com sucesso.',
            'data' => $tarefa,
        ]);
    }

    /**
     * Atualizar apenas o status da tarefa
     */
    public function tarefasUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:'.$this->statusList,
        ]);

        $tarefa = Tarefa::findOrFail($id);
        $tarefa->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso.',
            'data' => $tarefa,
        ]);
    }

    /**
     * Excluir tarefa
     */
    public function tarefasDestroy($id)
    {
        $tarefa = Tarefa::findOrFail($id);
        $tarefa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarefa excluída com sucesso.',
        ]);
    }

    // ==================== ANEXOS ====================

    /**
     * Listar todos os anexos de uma tarefa
     */
    public function anexosIndex($id_tarefa)
    {
        $tarefa = Tarefa::findOrFail($id_tarefa);
        $anexos = $tarefa->anexos;

        return response()->json([
            'success' => true,
            'data' => $anexos,
        ]);
    }

    /**
     * Obter detalhes de um anexo
     */
    public function anexosShow($id)
    {
        $anexo = Anexo::with('tarefas', 'uploader')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $anexo,
        ]);
    }

    /**
     * Fazer upload de anexo para uma tarefa
     */
    public function anexosStore(Request $request, $id_tarefa)
    {
        $request->validate([
            'arquivo' => 'required|file|max:10240',
        ]);

        $tarefa = Tarefa::findOrFail($id_tarefa);
        $file = $request->file('arquivo');

        $path = $file->store('treetask_uploads');

        $anexo = Anexo::create([
            'id_user_upload' => auth()->user()->id,
            'nome_arquivo' => $file->getClientOriginalName(),
            'path_arquivo' => $path,
            'mime_type' => $file->getMimeType(),
            'tamanho' => $file->getSize(),
        ]);

        $tarefa->anexos()->attach($anexo->id_anexo);

        return response()->json([
            'success' => true,
            'message' => 'Anexo criado com sucesso.',
            'data' => $anexo,
        ], 201);
    }

    /**
     * Excluir anexo
     */
    public function anexosDestroy($id_tarefa, $id_anexo)
    {
        $anexo = Anexo::findOrFail($id_anexo);

        $tarefa = Tarefa::findOrFail($id_tarefa);
        $tarefa->anexos()->detach($id_anexo);

        if ($anexo->tarefas()->count() == 0) {
            Storage::delete($anexo->path_arquivo);
            $anexo->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Anexo excluído com sucesso.',
        ]);
    }

    /**
     * Download do anexo (retorna URL assinada ou arquivo)
     */
    public function anexosDownload($id_anexo)
    {
        $anexo = Anexo::findOrFail($id_anexo);

        if (! Storage::exists($anexo->path_arquivo)) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo físico não encontrado.',
            ], 404);
        }

        return Storage::download($anexo->path_arquivo, $anexo->nome_arquivo);
    }
}

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

    // ==================== ENDPOINTS ESPECIAIS PARA INTEGRAÇÃO ====================

    /**
     * Health Check - Verifica se a API está funcionando
     */
    public function health()
    {
        try {
            // Testa conexão com banco
            \DB::connection()->getPdo();

            return response()->json([
                'status' => 'ok',
                'database' => 'connected',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'database' => 'disconnected',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar tarefas com filtros avançados (Dashboard)
     * GET /api/tarefas?filtro=pendentes&limit=5
     */
    public function tarefasList(Request $request)
    {
        $query = Tarefa::query()
            ->with(['fase.projeto', 'responsavel']);

        // Filtros de status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'nao_concluidas') {
                $query->where('status', '!=', 'Concluído');
            } elseif (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        // Filtro de prioridade
        if ($request->has('prioridade')) {
            $query->where('prioridade', $request->input('prioridade'));
        }

        // Filtro de projeto
        if ($request->has('projeto_id')) {
            $query->whereHas('fase', function ($q) use ($request) {
                $q->where('id_projeto', $request->input('projeto_id'));
            });
        }

        // Filtro de responsável
        if ($request->has('responsavel_id')) {
            $query->where('id_user_responsavel', $request->input('responsavel_id'));
        }

        // Filtro de data de vencimento
        if ($request->has('vencimento_ate')) {
            $query->where(function ($q) use ($request) {
                $q->where('data_vencimento', '<=', $request->input('vencimento_ate'))
                    ->orWhereNull('data_vencimento');
            });
        }

        // Ordenação padrão: prioridade DESC, data_vencimento ASC
        $query->orderByRaw("CASE prioridade 
            WHEN 'Urgente' THEN 4 
            WHEN 'Alta' THEN 3 
            WHEN 'Média' THEN 2 
            WHEN 'Baixa' THEN 1 
            ELSE 0 
        END DESC")
            ->orderByRaw('ISNULL(data_vencimento), data_vencimento ASC');

        // Paginação ou limite
        $limit = $request->input('limit', 50);
        $tarefas = $query->limit($limit)->get();

        // Formata resposta
        $data = $tarefas->map(function ($tarefa) {
            return $this->formatTarefa($tarefa);
        });

        return response()->json([
            'tarefas' => $data,
            'meta' => [
                'total' => $data->count(),
                'filtro_aplicado' => $request->input('status', 'todos'),
            ],
        ]);
    }

    /**
     * Top 3 tarefas para relatório da manhã
     * GET /api/tarefas/relatorio?dias=7&limit=3
     */
    public function tarefasRelatorio(Request $request)
    {
        $dias = $request->input('dias', 7);
        $limit = $request->input('limit', 3);
        $dataLimite = now()->addDays($dias);

        $query = Tarefa::query()
            ->with(['fase.projeto', 'responsavel'])
            ->where('status', '!=', 'Concluído')
            ->where(function ($q) use ($dataLimite) {
                $q->where('data_vencimento', '<=', $dataLimite)
                    ->orWhereNull('data_vencimento');
            });

        // Ordenação: prioridade DESC, data_vencimento ASC, ordem ASC
        $query->orderByRaw("CASE prioridade 
            WHEN 'Urgente' THEN 4 
            WHEN 'Alta' THEN 3 
            WHEN 'Média' THEN 2 
            WHEN 'Baixa' THEN 1 
            ELSE 0 
        END DESC")
            ->orderByRaw('ISNULL(data_vencimento), data_vencimento ASC')
            ->orderBy('ordem', 'ASC');

        $tarefas = $query->limit($limit)->get();

        $data = $tarefas->map(function ($tarefa) {
            return $this->formatTarefa($tarefa);
        });

        return response()->json([
            'tarefas' => $data,
            'meta' => [
                'total' => $data->count(),
                'periodo_dias' => $dias,
                'data_limite' => $dataLimite->toDateString(),
            ],
        ]);
    }

    /**
     * Identificar tarefas paradas (sem progresso há X horas)
     * GET /api/tarefas/paradas?horas=24
     */
    public function tarefasParadas(Request $request)
    {
        $horas = $request->input('horas', 24);
        $dataLimite = now()->subHours($horas);

        $tarefas = Tarefa::query()
            ->with(['fase.projeto', 'responsavel'])
            ->where('status', 'Em Andamento')
            ->where('updated_at', '<', $dataLimite)
            ->orderBy('updated_at', 'ASC')
            ->get();

        $data = $tarefas->map(function ($tarefa) {
            $formatted = $this->formatTarefa($tarefa);
            $updatedAt = is_string($tarefa->updated_at) ? \Carbon\Carbon::parse($tarefa->updated_at) : $tarefa->updated_at;
            $formatted['horas_parada'] = $updatedAt->diffInHours(now());
            $formatted['ultima_atualizacao'] = $updatedAt->toISOString();

            return $formatted;
        });

        return response()->json([
            'tarefas' => $data,
            'meta' => [
                'total' => $data->count(),
                'horas_limite' => $horas,
                'data_corte' => $dataLimite->toISOString(),
            ],
        ]);
    }

    /**
     * Obter tarefa completa com todos os relacionamentos
     * GET /api/tarefas/{id}/completa?include=projeto,fase,responsavel
     */
    public function tarefasCompleta(Request $request, $id)
    {
        $includes = $request->input('include', 'projeto,fase,responsavel');
        $includeArray = explode(',', $includes);

        $query = Tarefa::query();

        // Carrega relacionamentos solicitados
        if (in_array('projeto', $includeArray) || in_array('fase', $includeArray)) {
            $query->with(['fase.projeto']);
        }
        if (in_array('responsavel', $includeArray)) {
            $query->with('responsavel');
        }
        if (in_array('anexos', $includeArray)) {
            $query->with('anexos');
        }

        $tarefa = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatTarefa($tarefa, true),
        ]);
    }

    /**
     * Formata tarefa para resposta padronizada
     */
    private function formatTarefa($tarefa, $completo = false)
    {
        $statusMap = [
            'A Fazer' => ['pendente', 1],
            'Planejamento' => ['pendente', 2],
            'Em Andamento' => ['andamento', 3],
            'Aguardando resposta' => ['andamento', 4],
            'Concluído' => ['concluida', 5],
        ];

        $prioridadeMap = [
            'Urgente' => 4,
            'Alta' => 3,
            'Média' => 2,
            'Baixa' => 1,
        ];

        $statusInfo = $statusMap[$tarefa->status] ?? ['desconhecido', 0];
        $prioridadeValor = $prioridadeMap[$tarefa->prioridade] ?? 0;

        // Helper para converter data para ISO string de forma segura
        $toISOString = function ($date) {
            if (is_string($date)) {
                return \Carbon\Carbon::parse($date)->toISOString();
            }

            return $date ? $date->toISOString() : null;
        };

        $data = [
            'id_tarefa' => $tarefa->id_tarefa,
            'titulo' => $tarefa->titulo,
            'descricao' => $tarefa->descricao,
            'status' => $tarefa->status,
            'status_codigo' => $statusInfo[0],
            'status_ordem' => $statusInfo[1],
            'prioridade' => $tarefa->prioridade,
            'prioridade_codigo' => $prioridadeValor,
            'data_vencimento' => $tarefa->data_vencimento ? $toISOString($tarefa->data_vencimento) : null,
            'data_criacao' => $toISOString($tarefa->created_at),
            'data_atualizacao' => $toISOString($tarefa->updated_at),
            'estimativa_tempo' => $tarefa->estimativa_tempo,
            'ordem' => $tarefa->ordem,
        ];

        if ($completo) {
            $data['id_fase'] = $tarefa->id_fase;
            $data['fase'] = $tarefa->fase ? [
                'id_fase' => $tarefa->fase->id_fase,
                'nome' => $tarefa->fase->nome,
                'ordem' => $tarefa->fase->ordem,
            ] : null;

            $data['projeto'] = $tarefa->fase && $tarefa->fase->projeto ? [
                'id_projeto' => $tarefa->fase->projeto->id_projeto,
                'nome' => $tarefa->fase->projeto->nome,
                'status' => $tarefa->fase->projeto->status,
            ] : null;

            $data['responsavel'] = $tarefa->responsavel ? [
                'id_user' => $tarefa->responsavel->id,
                'nome' => $tarefa->responsavel->name,
            ] : null;

            if (isset($tarefa->anexos)) {
                $data['anexos'] = $tarefa->anexos->map(function ($anexo) {
                    return [
                        'id_anexo' => $anexo->id_anexo,
                        'nome_arquivo' => $anexo->nome_arquivo,
                        'mime_type' => $anexo->mime_type,
                    ];
                });
            }
        } else {
            $data['fase'] = $tarefa->fase ? [
                'id_fase' => $tarefa->fase->id_fase,
                'nome' => $tarefa->fase->nome,
            ] : null;

            $data['projeto'] = $tarefa->fase && $tarefa->fase->projeto ? [
                'id_projeto' => $tarefa->fase->projeto->id_projeto,
                'nome' => $tarefa->fase->projeto->nome,
            ] : null;

            $data['responsavel'] = $tarefa->responsavel ? [
                'id_user' => $tarefa->responsavel->id,
                'nome' => $tarefa->responsavel->name,
            ] : null;
        }

        return $data;
    }
}

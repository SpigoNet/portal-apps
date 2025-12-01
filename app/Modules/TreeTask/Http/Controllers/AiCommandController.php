<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Tarefa; // Adicionado
use App\Services\IaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiCommandController extends Controller
{
    protected IaService $iaService;

    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    // Passo 1: Recebe o contexto via GET (ex: ?type=project&id=1)
    public function index(Request $request)
    {
        $type = $request->query('type');
        $id = $request->query('id');
        $contextLabel = null;

        // Busca apenas o nome para mostrar na tela de input
        if ($type && $id) {
            if ($type === 'project') {
                $p = Projeto::find($id);
                $contextLabel = "Projeto: " . ($p->nome ?? 'N/A');
            } elseif ($type === 'phase') {
                $f = Fase::with('projeto')->find($id);
                $contextLabel = "Fase: " . ($f->nome ?? 'N/A') . " (Proj: " . ($f->projeto->nome ?? '') . ")";
            } elseif ($type === 'task') {
                $t = Tarefa::find($id);
                $contextLabel = "Tarefa: " . ($t->titulo ?? 'N/A');
            }
        }

        return view('TreeTask::ai_command.index', compact('type', 'id', 'contextLabel'));
    }

    public function preview(Request $request)
    {
        $request->validate(['prompt' => 'required|string|max:4000']);

        $userPrompt = $request->input('prompt');
        $type = $request->input('type');
        $id = $request->input('id');
        $userId = auth()->id();

        // --- 1. Construção do Contexto Específico ---
        $specificContextData = "";

        if ($type === 'project' && $id) {
            $projeto = Projeto::with(['fases.tarefas'])->find($id);
            if ($projeto) {
                $specificContextData .= "CONTEXTO DO PROJETO SELECIONADO:\n";
                $specificContextData .= "ID: {$projeto->id_projeto} | Nome: {$projeto->nome}\n";
                $specificContextData .= "Descrição: {$projeto->descricao}\n";
                $specificContextData .= "FASES E TAREFAS ATUAIS:\n";
                foreach($projeto->fases as $fase) {
                    $specificContextData .= "- Fase ID {$fase->id_fase}: {$fase->nome}\n";
                    foreach($fase->tarefas as $tarefa) {
                        $specificContextData .= "   * Tarefa ID {$tarefa->id_tarefa}: {$tarefa->titulo} (Status: {$tarefa->status}, Prio: {$tarefa->prioridade})\n";
                    }
                }
            }
        }
        elseif ($type === 'phase' && $id) {
            $fase = Fase::with(['projeto', 'tarefas'])->find($id);
            if ($fase) {
                $specificContextData .= "CONTEXTO DA FASE SELECIONADA:\n";
                $specificContextData .= "Projeto: {$fase->projeto->nome} (ID Proj: {$fase->id_projeto})\n";
                $specificContextData .= "Fase ID: {$fase->id_fase} | Nome: {$fase->nome}\n";
                $specificContextData .= "TAREFAS NESTA FASE:\n";
                foreach($fase->tarefas as $tarefa) {
                    $specificContextData .= "   * Tarefa ID {$tarefa->id_tarefa}: {$tarefa->titulo} (Status: {$tarefa->status})\n";
                }
            }
        }
        elseif ($type === 'task' && $id) {
            $tarefa = Tarefa::with('fase.projeto')->find($id);
            if ($tarefa) {
                $specificContextData .= "CONTEXTO DA TAREFA SELECIONADA:\n";
                $specificContextData .= "ID: {$tarefa->id_tarefa} | Título: {$tarefa->titulo}\n";
                $specificContextData .= "Descrição: {$tarefa->descricao}\n";
                $specificContextData .= "Status Atual: {$tarefa->status} | Prioridade: {$tarefa->prioridade}\n";
                $specificContextData .= "Local: Projeto '{$tarefa->fase->projeto->nome}' > Fase '{$tarefa->fase->nome}'\n";
            }
        }

        // --- 2. Contexto Geral (Backup para a IA saber de outros IDs se necessário) ---
        // Buscamos apenas listas simples para não estourar tokens
        $projetos = Projeto::where('id_user_owner', $userId)->take(20)->get(['id_projeto', 'nome']);
        $generalContext = "OUTROS PROJETOS DISPONÍVEIS (ID: Nome): " . $projetos->map(fn($p) => "{$p->id_projeto}: {$p->nome}")->implode(", ");

        // --- 3. Schema (Mantido) ---
        $schema = <<<EOT
        TABELAS:
        1. treetask_projetos (id_projeto, nome, descricao, status, id_user_owner)
        2. treetask_tarefas (id_tarefa, id_fase, titulo, descricao, status, id_user_responsavel, prioridade, data_vencimento)
           - status enum: 'A Fazer','Em Andamento','Concluído','Planejamento','Aguardando resposta'
           - prioridade enum: 'Baixa','Média','Alta','Urgente'
        3. treetask_fases (id_fase, id_projeto, nome)
        EOT;

        // --- 4. Prompt Final ---
        $systemPrompt = <<<EOT
        Você é um Assistente SQL TreeTask.

        CONTEXTO ATIVO:
        {$specificContextData}

        {$generalContext}

        SCHEMA DB:
        {$schema}

        OBJETIVO:
        Gere um JSON com "html_preview" e "sql_command" baseado no pedido do usuário e no contexto ativo.
        - Se o usuário pedir algo sobre "este projeto" ou "esta fase", use os IDs fornecidos no contexto ativo.
        - Para "sql_command": Gere SQL MySQL válido (INSERT/UPDATE/DELETE). Sem transações.
        - Para "html_preview": Resumo visual do que será feito.
        - User ID atual: {$userId}.
        EOT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        try {
            $response = $this->iaService->generateText($messages, ['temperature' => 0.2]);
            $cleanResponse = str_replace(['```json', '```'], '', $response);
            $data = json_decode($cleanResponse, true);

            if (!$data || !isset($data['sql_command'])) {
                // Tentar extrair JSON na força bruta caso venha texto junto
                preg_match('/\{.*\}/s', $cleanResponse, $matches);
                if(isset($matches[0])) {
                    $data = json_decode($matches[0], true);
                }
                if (!$data || !isset($data['sql_command'])) {
                    Log::log('error', 'Falha ao parsear JSON da IA. Resposta bruta: ' . $response);
                    throw new \Exception("Falha ao parsear JSON da IA.");
                }

            }

            return view('TreeTask::ai_command.preview', [
                'prompt' => $userPrompt,
                'html_preview' => $data['html_preview'],
                'sql_command' => $data['sql_command']
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Erro: ' . $e->getMessage()]);
        }
    }

    public function execute(Request $request)
    {
        $request->validate(['sql_command' => 'required|string']);
        $sql = $request->input('sql_command');

        if (stripos($sql, 'DROP TABLE') !== false || stripos($sql, 'users') !== false) {
            return back()->withErrors(['msg' => 'Comando SQL bloqueado por segurança.']);
        }

        try {
            DB::beginTransaction();
            DB::unprepared($sql);
            DB::commit();

            return redirect()->route('treetask.index') // Poderíamos redirecionar de volta para o contexto original se salvássemos a URL
            ->with('success', 'Comandos IA executados!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro SQL: ' . $e->getMessage());
        }
    }
}

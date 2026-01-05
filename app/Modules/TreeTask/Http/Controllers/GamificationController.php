<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Tarefa;
use App\Services\IaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    protected IaService $iaService;

    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    public function motivacao(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Verifica se o usuário pediu ajuda para uma tarefa específica
        $taskId = $request->input('task_id');

        if ($taskId) {
            $tarefaAtual = Tarefa::find($taskId);
        } else {
            // Se não, busca a que está em andamento (Hiperfoco)
            $tarefaAtual = Tarefa::where('id_user_responsavel', $userId)
                ->where('status', 'Em Andamento')
                ->first();
        }

        if (!$tarefaAtual) {
            return response()->json([
                'message' => 'Nenhuma missão ativa no radar. Selecione uma tarefa para iniciar o protocolo.',
                'universo' => 'Standby'
            ]);
        }

        // Prompt Engenharia Reversa: Focado em Alto QI Visual + Baixa Iniciação
        $systemPrompt = <<<EOT
ATUAR COMO: Um Estrategista Lógico e Pragmático (Estilo Sci-Fi/Cyberpunk).
CONTEXTO: O usuário tem inteligência visual alta, mas dificuldade severa de iniciação (TDAH).
OBJETIVO: Receba a tarefa e quebre-a em 3 passos microscópicos, ridículos de tão fáceis, para vencer a inércia.
REGRAS:
1. Nada de papo motivacional abstrato ("Você consegue!").
2. Use verbos de ação física ou visual ("Abrir", "Escrever", "Desenhar").
3. Formato: Lista curta. Use emojis técnicos (🛠, 💻, ⚡).
EOT;

        $userPrompt = "Estou travado na tarefa: '{$tarefaAtual->titulo}'. Descrição: '{$tarefaAtual->descricao}'. O que faço agora?";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // Temperatura baixa para respostas mais diretas e lógicas
        $textoGerado = $this->iaService->generateText($messages, ['temperature' => 0.4]);

        if (!$textoGerado) {
            $textoGerado = "Erro de conexão com o núcleo estratégico. Tente reiniciar o passo 1 manualmente.";
        }

        return response()->json([
            'message' => $textoGerado,
            'universo' => 'Estrategista Lógico'
        ]);
    }
}

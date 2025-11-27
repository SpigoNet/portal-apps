<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Models\LorePrompt;
use App\Services\IaService; // <--- Importamos o Service Global
use Illuminate\Http\JsonResponse;

class GamificationController extends Controller
{
    protected IaService $iaService;

    // Injeção de Dependência do Service
    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    public function motivacao(): JsonResponse
    {
        $userId = auth()->id();

        // 1. Buscar tarefas pendentes
        $tarefas = Tarefa::where('id_user_responsavel', $userId)
            ->where('status', '!=', 'Concluído')
            ->orderBy('prioridade', 'asc')
            ->take(10) // Limita a 10 para não estourar tokens
            ->get();

        if ($tarefas->isEmpty()) {
            return response()->json([
                'message' => 'Você não tem missões pendentes. O reino está em paz... por enquanto.',
                'universo' => 'Paz Interior'
            ]);
        }

        // 2. Formatar lista de tarefas para o prompt do USUÁRIO
        $listaTarefas = $tarefas->map(function($t) {
            return "- {$t->titulo} (Prioridade: {$t->prioridade})";
        })->implode("\n");

        // 3. Sortear um Universo (Lore) para o prompt do SISTEMA
        $lore = LorePrompt::where('ativo', true)->inRandomOrder()->first();

        // Fallback caso não tenha lore cadastrada
        if (!$lore) {
            $lore = (object) [
                'universo' => 'Assistente Padrão',
                'prompt_personagem' => 'Você é um assistente pessoal eficiente e levemente sarcástico.'
            ];
        }

        // 4. Montar a estrutura de mensagens para o Service
        $messages = [
            [
                'role' => 'system',
                'content' => "ATUAR COMO: {$lore->prompt_personagem}. " .
                    "OBJETIVO: Motivar o usuário a completar suas tarefas. " .
                    "FORMATO: Mensagem curta (máx 300 caracteres). Use emojis."
            ],
            [
                'role' => 'user',
                'content' => "Aqui estão minhas tarefas pendentes hoje:\n{$listaTarefas}\n\nO que devo fazer?"
            ]
        ];

        // 5. Chamar o Service
        // Podemos passar opções extras se quisermos, ex: ['temperature' => 0.9] para ser mais criativo
        $textoGerado = $this->iaService->generateText($messages, ['temperature' => 1]);

        // Tratamento de erro caso a API falhe
        if (!$textoGerado) {
            $textoGerado = "A conexão com o multiverso {$lore->universo} está instável. Mas suas tarefas continuam aqui!";
        }

        return response()->json([
            'message' => $textoGerado,
            'universo' => $lore->universo
        ]);
    }
}

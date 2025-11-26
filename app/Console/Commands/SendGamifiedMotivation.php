<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Models\LorePrompt;
use App\Services\PollinationService;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class SendGamifiedMotivation extends Command
{
    protected $signature = 'treetask:daily-motivation {user_id? : ID opcional para enviar apenas para um usuário}';
    protected $description = 'Gera e envia a mensagem motivacional diária baseada nas tarefas pendentes.';

    protected PollinationService $pollinationService;

    public function __construct(PollinationService $pollinationService)
    {
        parent::__construct();
        $this->pollinationService = $pollinationService;
    }

    public function handle()
    {
        $this->info('Iniciando processo de motivação diária...');

        $userId = $this->argument('user_id');
        $users = $userId ? User::where('id', $userId)->get() : User::all();

        foreach ($users as $user) {
            $this->processUser($user);
        }

        $this->info('Processo finalizado.');
        return Command::SUCCESS;
    }

    private function processUser($user)
    {
        $this->info("Processando usuário: {$user->name} (ID: {$user->id})");

        // 1. Buscar tarefas pendentes do usuário
        // ADICIONADO: with(['fase.projeto']) para carregar os dados relacionados de forma otimizada
        $tarefas = Tarefa::with(['fase.projeto'])
            ->where('id_user_responsavel', $user->id)
            ->where('status', 'Em Andamento')
            ->orderBy('prioridade', 'asc') // Urgentes primeiro
            ->take(15) // Aumentei um pouco o limite já que a lista é feita via PHP
            ->get();

        if ($tarefas->isEmpty()) {
            $this->line(" - Sem tarefas em andamento. Gerando mensagem de aviso...");

            // 3. Sortear Universo (Lore) para contextualizar o tom da IA
            $lore = LorePrompt::where('ativo', true)->inRandomOrder()->first();

            // Fallback
            if (!$lore) {
                $lore = (object) [
                    'universo' => 'Padrão',
                    'prompt_personagem' => 'Você é um assistente pessoal eficiente.'
                ];
            }

            // Montar mensagens para o Service (IA) indicando 0 tarefas e pedindo incentivo para escolher novas
            $messages = [
                [
                    'role' => 'system',
                    'content' => "ATUAR COMO: {$lore->prompt_personagem}. " .
                        "OBJETIVO: Criar APENAS uma introdução motivacional curta (máx 200 caracteres) sobre reengajamento quando não há tarefas. " .
                        "INSTRUÇÃO: Informe que não há tarefas e incentive a escolher novas. Use emojis. Seja direto."
                ],
                [
                    'role' => 'user',
                    'content' => "Olá! Hoje eu tenho 0 tarefas críticas em andamento. Me motive e me lembre para escolher novas tarefas!"
                ]
            ];

            $this->line(" - Gerando texto no estilo: {$lore->universo}...");
            $textoGerado = $this->pollinationService->generateText($messages, ['temperature' => 1]);

            if ($textoGerado) {
                $mensagemFinal = $textoGerado .
                    "\n\n📋 *Suas Missões em Andamento:*\n\nNenhuma no momento — escolha novas tarefas para continuar progredindo!";

                (new WhatsAppService())->sendToUser($user, $mensagemFinal);
                $this->info(" - Mensagem enviada (sem tarefas).");
            } else {
                $this->error(" - Falha ao gerar texto para o usuário {$user->id} (sem tarefas)");
            }

            return;
        }

        // 2. Preparar lista rica para o WhatsApp (Feito no PHP)
        $listaTarefas = $tarefas->map(function($t) {
            // Define ícone baseado na prioridade
            $icon = match($t->prioridade) {
                'Urgente' => '🔴',
                'Alta' => '🟠',
                default => '▫️'
            };

            $projeto = $t->fase->projeto->nome ?? 'Geral';
            $fase = $t->fase->nome ?? 'Andamento';

            // Formato:
            // 🔴 Título da Tarefa
            //    ↳ 📂 Nome do Projeto › 📌 Nome da Fase
            return "{$icon} *{$t->titulo}*\n   ↳ 📂 {$projeto} › 📌 {$fase}";
        })->implode("\n\n");

        // 3. Sortear Universo (Lore)
        $lore = LorePrompt::where('ativo', true)->inRandomOrder()->first();

        // Fallback
        if (!$lore) {
            $lore = (object) [
                'universo' => 'Padrão',
                'prompt_personagem' => 'Você é um assistente pessoal eficiente.'
            ];
        }

        // 4. Montar Mensagens para o Service (IA recebe apenas a quantidade)
        $messages = [
            [
                'role' => 'system',
                'content' => "ATUAR COMO: {$lore->prompt_personagem}. " .
                    "OBJETIVO: Criar APENAS uma introdução motivacional curta (máx 200 caracteres) sobre foco. " .
                    "INSTRUÇÃO: Não liste as tarefas, apenas mencione a quantidade e motive. Use emojis. Seja direto."
            ],
            [
                'role' => 'user',
                'content' => "Olá! Hoje eu tenho " . $tarefas->count() . " tarefas críticas em andamento. Me motive!"
            ]
        ];

        // 5. Gerar Texto via PollinationService
        $this->line(" - Gerando texto no estilo: {$lore->universo}...");
        $textoGerado = $this->pollinationService->generateText($messages, ['temperature' => 1]);

        if ($textoGerado) {
            // Monta a mensagem final: Texto da IA + Cabeçalho da Lista + Lista Formatada
            $mensagemFinal = $textoGerado . "\n\n📋 *Suas Missões em Andamento:*\n\n" . $listaTarefas;

            (new WhatsAppService())->sendToUser($user, $mensagemFinal);
            $this->info(" - Mensagem enviada!");
        } else {
            $this->error(" - Falha ao gerar texto para o usuário {$user->id}");
        }
    }
}

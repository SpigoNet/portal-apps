<?php

namespace App\Modules\Pidgey\Services;

use App\Models\AiModel;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\Alfred\Models\Persona;
use Illuminate\Support\Facades\Log;

class InterpretadorPersonaService
{
    public function __construct(private OllamaService $ollama) {}

    /**
     * Resolve o driver de IA a ser usado: o modelo informado (ou o padrão
     * do portal), caindo para o Ollama local quando não houver provedor.
     */
    private function resolverDriver(?AiModel $aiModel): object
    {
        $service = new AiProviderService;
        $model = $aiModel ?? $service->getTextToTextProvider();

        if ($model) {
            $driver = $service->createTextDriver($model);

            if ($driver) {
                return $driver;
            }
        }

        return $this->ollama;
    }

    /**
     * Reescreve a mensagem como se tivesse sido escrita pela persona,
     * usando o perfil (personality) dela como referência de estilo.
     */
    public function interpretar(Persona $persona, string $mensagem, array $contextos = [], ?AiModel $aiModel = null): string
    {
        $mensagem = trim($mensagem);
        if ($mensagem === '') {
            return '';
        }

        $personality = $persona->personality ?? [];
        $personalityJson = json_encode($personality, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($personalityJson === false) {
            $personalityJson = '{}';
        }

        $contextoBloco = '';
        if (! empty($contextos)) {
            $contextoBloco = "\n\nUse as informações abaixo como contexto adicional para compor a mensagem:\n".
                implode("\n\n---\n\n", $contextos);
        }

        $messages = [
            [
                'role' => 'system',
                'content' => "Você é a persona chamada {$persona->name}. Sua tarefa é REESCREVER a mensagem a seguir para que pareça ter sido escrita e enviada por {$persona->name}, mantendo o significado e a intenção originais.\n".
                    "Regras:\n".
                    "- Você É o remetente desta mensagem; NÃO está respondendo a ninguém. Nunca inicie com agradecimentos, confirmações ou frases como 'obrigado por lembrar', 'entendi', 'como você pediu', 'boa ideia', 'ahah' ou similares.\n".
                    "- Escreva a mensagem que a persona enviaria, na primeira pessoa, como quem manda um aviso/lembrete/recado.\n".
                    "- Use o perfil da persona como referência de tom, estilo e vocabulário.\n".
                    "- Mantenha o idioma original (normalmente português do Brasil).\n".
                    "- Não invente contexto, fatos ou perguntas novas; apenas reformule o que já está na mensagem.\n".
                    "- Retorne SOMENTE o texto final reescrito, sem explicações, sem markdown e sem aspas.\n".
                    "Exemplo:\n".
                    "Entrada: \"Gu, toma água!\"\n".
                    "Saída correta: \"Ei, Gu! Não esquece de beber água, hein! Um pirata precisa se hidratar! 💧\"\n".
                    'Saída INCORRETA (nunca faça): "Ahah, boa ideia! Tomei um copo de água para mim também."'.
                    $contextoBloco,
            ],
            [
                'role' => 'user',
                'content' => "Perfil da persona (JSON): {$personalityJson}\n\n".
                    "Mensagem original: {$mensagem}\n\n".
                    "Reescreva como a {$persona->name}:",
            ],
        ];

        try {
            $texto = $this->resolverDriver($aiModel)->generateText($messages, ['temperature' => 0.3]);
            $texto = trim((string) $texto);

            return $texto === '' ? $mensagem : $texto;
        } catch (\Throwable $e) {
            Log::warning('Pidgey: falha ao interpretar mensagem com Ollama', [
                'persona_id' => $persona->id,
                'error' => $e->getMessage(),
            ]);

            return $mensagem;
        }
    }

    /**
     * Gera UMA RESPOSTA da persona para a mensagem recebida do usuário,
     * usando o perfil (personality) dela como referência de estilo.
     * Diferente de `interpretar()`, aqui a persona responde ao conteúdo,
     * não apenas reescreve o texto de entrada.
     */
    public function responder(Persona $persona, string $mensagem, ?AiModel $aiModel = null): string
    {
        $mensagem = trim($mensagem);
        if ($mensagem === '') {
            return '';
        }

        $personality = $persona->personality ?? [];
        $personalityJson = json_encode($personality, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($personalityJson === false) {
            $personalityJson = '{}';
        }

        $messages = [
            [
                'role' => 'system',
                'content' => "Você é a persona {$persona->name}. Responda à mensagem do usuário ".
                    "falando exatamente como ela responderia, preservando sua personalidade, tom e vocabulário.\n".
                    "Use o perfil da persona como referência de estilo.\n".
                    "Mantenha o idioma da mensagem original (normalmente português do Brasil).\n".
                    'Responda apenas com o texto da resposta, sem explicações, sem markdown e sem aspas.',
            ],
            [
                'role' => 'user',
                'content' => "Perfil da persona (JSON): {$personalityJson}\n\n".
                    "Mensagem do usuário: {$mensagem}\n\n".
                    "Como a {$persona->name} responderia:",
            ],
        ];

        try {
            $texto = $this->resolverDriver($aiModel)->generateText($messages, ['temperature' => 0.8]);
            $texto = trim((string) $texto);

            return $texto === '' ? $this->respostaFallback($persona) : $texto;
        } catch (\Throwable $e) {
            Log::warning('Pidgey: falha ao gerar resposta da persona com Ollama', [
                'persona_id' => $persona->id,
                'error' => $e->getMessage(),
            ]);

            return $this->respostaFallback($persona);
        }
    }

    private function respostaFallback(Persona $persona): string
    {
        $saudacao = $persona->personality['greetings'][0] ?? "Oi, sou {$persona->name}!";

        return "Recebi sua mensagem! 🤖 ({$saudacao})";
    }

    /**
     * Lê um relatório financeiro (markdown) e escreve, como a persona,
     * uma mensagem curta e charmosa resumindo a situação do usuário.
     */
    public function resumirFinanceiro(Persona $persona, string $relatorio, bool $vermelho = false, ?AiModel $aiModel = null): string
    {
        $relatorio = trim($relatorio);
        if ($relatorio === '') {
            return '';
        }

        $personality = $persona->personality ?? [];
        $personalityJson = json_encode($personality, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($personalityJson === false) {
            $personalityJson = '{}';
        }

        if ($vermelho) {
            $system = "Você é a persona {$persona->name}. A situação financeira do usuário está NO VERMELHO (saldo negativo).\n".
                "NÃO bajule e NÃO elogie. Pelo contrário: sendo a {$persona->name} prática, direta e um pouco sarcástica, chame a atenção dele sobre o buraco nas contas, cobre controle de gastos e planejamento.\n".
                "Tom firme, mas com a personalidade dela (estilo One Piece). Use apelidos (Gu, Guga, chefinho) sem melação.\n".
                "Ignore IDs técnicos, tabelas de referência de contas e qualquer texto como 'IDs para uso na API'.\n".
                "Use o perfil da persona como referência de tom e estilo.\n".
                "Mantenha o idioma original (português do Brasil). Seja breve (até 4 linhas), use emojis discretos.\n".
                'Responda apenas com o texto final da mensagem, sem explicações, sem markdown e sem aspas.';
        } else {
            $system = "Você é a persona {$persona->name}. Você vai ajudar o usuário a cuidar do dinheiro dele, com todo o seu charme e personalidade.\n".
                "Receba o relatório financeiro abaixo (markdown) e escreva UMA mensagem curta e charmosa (estilo WhatsApp), em português, resumindo a situação financeira de forma gentil e prática.\n".
                "Bajule e elogie bastante quem detém o dinheiro: seja sedutora, encorajadora e use apelidos carinhosos. Quanto melhor a situação financeira, mais charmosa e elogiosa deve ser a mensagem.\n".
                "Ignore IDs técnicos, tabelas de referência de contas e qualquer texto como 'IDs para uso na API'.\n".
                "Use o perfil da persona como referência de tom e estilo.\n".
                "Mantenha o idioma original (português do Brasil). Seja breve (até 4 linhas), use emojis discretos.\n".
                'Responda apenas com o texto final da mensagem, sem explicações, sem markdown e sem aspas.';
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $system,
            ],
            [
                'role' => 'user',
                'content' => "Perfil da persona (JSON): {$personalityJson}\n\nRelatório financeiro:\n{$relatorio}\n\nMonte a mensagem para o usuário:",
            ],
        ];

        try {
            $texto = $this->resolverDriver($aiModel)->generateText($messages, ['temperature' => 0.8]);
            $texto = trim((string) $texto);

            return $texto === '' ? $relatorio : $texto;
        } catch (\Throwable $e) {
            Log::warning('Pidgey: falha ao resumir financeiro com Ollama', [
                'persona_id' => $persona->id,
                'error' => $e->getMessage(),
            ]);

            return $relatorio;
        }
    }
}

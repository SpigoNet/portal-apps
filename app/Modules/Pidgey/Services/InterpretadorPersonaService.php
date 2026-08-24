<?php

namespace App\Modules\Pidgey\Services;

use App\Modules\Alfred\Models\Persona;
use Illuminate\Support\Facades\Log;

class InterpretadorPersonaService
{
    public function __construct(private OllamaService $ollama) {}

    /**
     * Reescreve a mensagem como se tivesse sido escrita pela persona,
     * usando o perfil (personality) dela como referência de estilo.
     */
    public function interpretar(Persona $persona, string $mensagem): string
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
                'content' => "Você é a persona chamada {$persona->name}. Reescreva a mensagem do usuário ".
                    "falando exatamente como ela falaria, preservando o significado original.\n".
                    "Use o perfil abaixo como referência de tom, estilo e vocabulário.\n".
                    "Mantenha o idioma da mensagem original (normalmente português do Brasil).\n".
                    'Responda apenas com o texto final da mensagem, sem explicações, sem markdown e sem aspas.',
            ],
            [
                'role' => 'user',
                'content' => "Perfil da persona (JSON): {$personalityJson}\n\n".
                    "Mensagem original: {$mensagem}\n\n".
                    "Reescreva como a {$persona->name}:",
            ],
        ];

        try {
            $texto = $this->ollama->generateText($messages, ['temperature' => 0.8]);
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
     * Lê um relatório financeiro (markdown) e escreve, como a persona,
     * uma mensagem curta e charmosa resumindo a situação do usuário.
     */
    public function resumirFinanceiro(Persona $persona, string $relatorio, bool $vermelho = false): string
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
            $texto = $this->ollama->generateText($messages, ['temperature' => 0.8]);
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

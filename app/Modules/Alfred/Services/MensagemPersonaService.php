<?php

namespace App\Modules\Alfred\Services;

use App\Modules\Alfred\Models\Persona;
use App\Services\IaService;
use Illuminate\Support\Facades\Log;

class MensagemPersonaService
{
    public function __construct(private IaService $iaService) {}

    public function gerarMensagem(Persona $persona, string $instrucao): string
    {
        $instrucao = trim($instrucao);
        if ($instrucao === '') {
            return $this->fallbackMensagem($persona, 'Lembrete importante.');
        }

        $personality = $persona->personality ?? [];
        $personalityJson = json_encode($personality, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($personalityJson === false) {
            $personalityJson = '{}';
        }

        $messages = [
            [
                'role' => 'system',
                'content' => "Você cria mensagens curtas de WhatsApp em português do Brasil no estilo da persona.\n".
                    "Responda apenas com o texto final da mensagem, sem explicações, sem markdown e sem aspas.\n".
                    "Mantenha tom humano, acolhedor e direto.",
            ],
            [
                'role' => 'user',
                'content' => "Persona: {$persona->name}\n".
                    "Perfil da persona (JSON): {$personalityJson}\n".
                    "Intenção da mensagem: {$instrucao}\n".
                    "Gere a mensagem final para envio.",
            ],
        ];

        try {
            $textoGerado = $this->iaService->generateText($messages, ['temperature' => 0.7]);
            $textoGerado = trim((string) $textoGerado);

            if ($textoGerado === '') {
                return $this->fallbackMensagem($persona, $instrucao);
            }

            // Campo de agendamento usa limite de 2000 caracteres; mantemos o texto final no mesmo tamanho.
            return mb_substr($textoGerado, 0, 2000);
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar mensagem com IA para persona', [
                'persona_id' => $persona->id,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackMensagem($persona, $instrucao);
        }
    }

    private function fallbackMensagem(Persona $persona, string $instrucao): string
    {
        $greetings = $persona->personality['greetings'] ?? [];
        if (! is_array($greetings)) {
            $greetings = [];
        }

        $greeting = trim((string) ($greetings[0] ?? ''));

        if ($greeting !== '') {
            return mb_substr($greeting."\n".$instrucao, 0, 2000);
        }

        return mb_substr($instrucao, 0, 2000);
    }
}

<?php

namespace App\Modules\Pidgey\Services;

use App\Modules\Alfred\Models\Persona;

class EnvioService
{
    public function __construct(
        private TelegramService $telegram,
        private InterpretadorPersonaService $interpretador,
    ) {}

    /**
     * Envia uma mensagem através da persona/canal informados.
     *
     * @return array{ok:bool,status:?int,error:?string,interpretada:bool,mensagem_final:string}
     */
    public function enviar(Persona $persona, string $mensagem, ?string $canal = null, bool $interpretar = false): array
    {
        $canal = $canal ?: ($persona->canal ?? 'telegram');

        if ($canal !== 'telegram') {
            return [
                'ok' => false,
                'status' => null,
                'error' => "Canal '{$canal}' ainda não suportado pelo Pidgey",
                'interpretada' => false,
                'mensagem_final' => $mensagem,
            ];
        }

        if (empty($persona->telegram_token) || empty($persona->telegram_chat_id)) {
            return [
                'ok' => false,
                'status' => null,
                'error' => 'Persona sem token/chat_id do Telegram configurado',
                'interpretada' => false,
                'mensagem_final' => $mensagem,
            ];
        }

        $mensagemFinal = $mensagem;
        $interpretada = false;

        if ($interpretar) {
            $mensagemFinal = $this->interpretador->interpretar($persona, $mensagem);
            $interpretada = $mensagemFinal !== $mensagem;
        }

        $result = $this->telegram->sendMessage(
            $persona->telegram_token,
            $persona->telegram_chat_id,
            $mensagemFinal
        );

        return [
            'ok' => $result['ok'],
            'status' => $result['status'] ?? null,
            'error' => $result['error'] ?? null,
            'interpretada' => $interpretada,
            'mensagem_final' => $mensagemFinal,
        ];
    }
}

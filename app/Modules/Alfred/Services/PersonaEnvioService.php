<?php

namespace App\Modules\Alfred\Services;

use App\Modules\Alfred\Models\Persona;

class PersonaEnvioService
{
    public function __construct(
        private EvolutionApiService $evo,
        private TelegramApiService $telegram,
    ) {}

    public function enviar(Persona $persona, string $mensagem): array
    {
        $canal = $persona->canal ?? 'whatsapp';

        if ($canal === 'telegram') {
            if (empty($persona->telegram_token) || empty($persona->telegram_chat_id)) {
                $msg = 'Persona sem token/chat_id do Telegram configurado';
                Log::warning($msg, ['persona_id' => $persona->id]);

                return ['ok' => false, 'status' => null, 'body' => null, 'error' => $msg];
            }

            return $this->telegram->sendMessage(
                $persona->telegram_token,
                $persona->telegram_chat_id,
                $mensagem
            );
        }

        if (empty($persona->whatsapp_group_jid)) {
            $msg = 'Persona sem grupo WhatsApp configurado';
            Log::warning($msg, ['persona_id' => $persona->id]);

            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $msg];
        }

        return $this->evo->sendTextToGroup($persona->whatsapp_group_jid, $mensagem);
    }
}

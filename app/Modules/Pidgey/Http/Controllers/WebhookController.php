<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;
use App\Modules\Pidgey\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function __construct(
        private TelegramService $telegram,
        private InterpretadorPersonaService $interpretador,
    ) {}

    /**
     * Recebe as atualizações (updates) do Telegram enviadas ao bot de uma
     * persona. Identifica a persona pelo slug na URL, gera uma RESPOSTA
     * (em vez de apenas reescrever a mensagem) e a envia de volta ao chat.
     */
    public function receber(Request $request, string $persona): JsonResponse
    {
        $persona = Persona::query()
            ->where('slug', $persona)
            ->first();

        if (! $persona instanceof Persona) {
            Log::warning('Pidgey webhook: persona não encontrada', ['slug' => $persona]);

            return response()->json(['ok' => false, 'error' => 'Persona não encontrada'], 404);
        }

        if (empty($persona->telegram_token)) {
            return response()->json(['ok' => false, 'error' => 'Persona sem token do Telegram'], 422);
        }

        $update = $request->json()->all();

        $chatId = $update['message']['chat']['id']
            ?? $update['edited_message']['chat']['id']
            ?? null;

        $texto = $update['message']['text']
            ?? $update['edited_message']['text']
            ?? null;

        if ($chatId === null || $texto === null) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $resposta = $this->interpretador->responder($persona, (string) $texto);

        $result = $this->telegram->sendMessage(
            $persona->telegram_token,
            (string) $chatId,
            $resposta
        );

        return response()->json([
            'ok' => $result['ok'],
            'status' => $result['status'] ?? null,
            'error' => $result['error'] ?? null,
        ]);
    }
}

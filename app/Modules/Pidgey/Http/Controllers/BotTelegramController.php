<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook dos bots por módulo. O segredo (cabeçalho X-Telegram-Bot-Api-Secret-Token) é derivado do slug,
 * então só o Telegram, que recebeu o segredo no setWebhook, consegue acionar o bot.
 */
class BotTelegramController
{
    public function __construct(private readonly TelegramRoteador $roteador) {}

    public function receber(Request $request, string $persona): JsonResponse
    {
        $segredo = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (! hash_equals(TelegramRoteador::segredo($persona), $segredo)) {
            return response()->json(['ok' => false], 403);
        }

        $modelo = Persona::where('slug', $persona)->where('active', true)->first();

        if ($modelo === null || empty($modelo->telegram_token)) {
            return response()->json(['ok' => false], 404);
        }

        // Sempre 200 depois de autenticado: o Telegram reenvia o update em loop se receber erro.
        $this->roteador->tratar($modelo, $request->json()->all());

        return response()->json(['ok' => true]);
    }
}

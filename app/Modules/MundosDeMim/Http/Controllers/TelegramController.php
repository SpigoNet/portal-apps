<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramController extends Controller
{
    public function conectar(TelegramLinkService $telegram): RedirectResponse
    {
        return redirect()->away($telegram->criarLink(Auth::user()));
    }

    public function desconectar(TelegramLinkService $telegram): RedirectResponse
    {
        $telegram->desconectar(Auth::user());

        return redirect()
            ->route('mundos-de-mim.perfil.index')
            ->with('success', 'Telegram desconectado. Os postais deixam de chegar por lá.');
    }

    public function webhook(Request $request, TelegramLinkService $telegram): JsonResponse
    {
        $segredo = (string) config('services.mundos_de_mim.telegram_webhook_secret', '');
        $recebido = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($segredo === '' || ! hash_equals($segredo, $recebido)) {
            return response()->json(['ok' => false], 403);
        }

        $telegram->tratarAtualizacao($request->json()->all());

        return response()->json(['ok' => true]);
    }
}

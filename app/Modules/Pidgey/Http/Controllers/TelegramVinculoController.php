<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\TelegramVinculo;
use App\Modules\Pidgey\Services\TelegramBotService;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramVinculoController
{
    public function __construct(
        private readonly TelegramRoteador $roteador,
        private readonly TelegramBotService $bots,
    ) {}

    public function index(Request $request): View
    {
        $personas = Persona::where('active', true)->whereNotNull('telegram_token')
            ->get()
            ->filter(fn (Persona $p): bool => $this->roteador->manipulador($p->slug) !== null)
            ->map(fn (Persona $p): array => ['persona' => $p, 'bot' => $this->bots->nome((string) $p->telegram_token)])
            ->values();

        return view('Pidgey::telegram.index', [
            'vinculo' => TelegramVinculo::where('user_id', $request->user()->id)->first(),
            'bots' => $personas,
            'codigo' => $request->session()->get('telegram_codigo'),
            'minutos' => TelegramRoteador::CODIGO_TTL_MINUTOS,
        ]);
    }

    public function gerarCodigo(Request $request): RedirectResponse
    {
        return redirect()->route('pidgey.telegram.index')
            ->with('telegram_codigo', $this->roteador->gerarCodigo($request->user()));
    }

    public function desvincular(Request $request): RedirectResponse
    {
        TelegramVinculo::where('user_id', $request->user()->id)->delete();

        return redirect()->route('pidgey.telegram.index')->with('success', 'Telegram desvinculado.');
    }
}

<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\LogDiaRuim;
use Illuminate\Support\Facades\Auth;

class DiaRuimController
{
    public function ativar()
    {
        $user = Auth::user();

        if ($user && $user->profile) {
            $user->profile->update([
                'modo_dia_ruim' => true,
                'dia_ruim_ativado_em' => now(),
            ]);

            LogDiaRuim::create([
                'user_id' => $user->id,
                'ativado_em' => now(),
            ]);
        }

        return redirect('/')
            ->with('dia_ruim', true)
            ->with('mensagem', '🫂 Cuidar de você é a prioridade hoje');
    }

    public function desativar()
    {
        $user = Auth::user();

        if ($user && $user->profile) {
            $user->profile->update([
                'modo_dia_ruim' => false,
                'dia_ruim_ativado_em' => null,
            ]);

            $ultimoLog = LogDiaRuim::doUsuario($user->id)->ativos()->first();
            if ($ultimoLog) {
                $ultimoLog->update(['desativado_em' => now()]);
            }
        }

        return redirect('/')
            ->with('success', 'Bom te ver de volta! 💚');
    }

    public function index()
    {
        $mensagens = [
            'Nem todo dia precisa ser produtivo.',
            'Descansar é fazer algo importante.',
            'Você está cuidando de si. Isso é válido.',
            'Amanhã é outro dia.',
            'Estou aqui quando precisar.',
            'Respirar é suficiente por agora.',
        ];

        $mensagem = $mensagens[array_rand($mensagens)];

        return view('Alfred::dia_ruim.index', compact('mensagem'));
    }
}

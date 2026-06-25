<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\ConsumoAgua;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Services\EvolutionApiService;
use Illuminate\Http\Request;

class HidratacaoController
{
    public function index()
    {
        $progresso = ConsumoAgua::progressoHoje();
        $historicoHoje = ConsumoAgua::hoje()->get();

        return view('Alfred::hidratacao.index', compact('progresso', 'historicoHoje'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'quantidade_ml' => 'required|integer|min:1',
        ]);

        ConsumoAgua::adicionar($validated['quantidade_ml']);

        $progresso = ConsumoAgua::progressoHoje();

        $mensagem = 'Água registrada! 💧';

        if ($progresso['percentual'] >= 100) {
            $mensagem .= ' Meta atingida! Parabéns! 🎉';
        } elseif ($progresso['percentual'] >= 75) {
            $mensagem .= ' Faltam '.$progresso['restante'].'ml para a meta.';
        }

        try {
            $chopper = Persona::where('slug', 'chopper')->where('active', true)->first();
            if ($chopper && $chopper->whatsapp_group_jid) {
                $evo = new EvolutionApiService;
                $text = ($chopper->personality['greetings'][0] ?? "Oi! Sou {$chopper->name}.")."\n";
                $text .= "Registro de hidratação: {$validated['quantidade_ml']}ml.";
                $evo->sendTextToGroup($chopper->whatsapp_group_jid, $text);
            }
        } catch (\Throwable $e) {
            //
        }

        return redirect()->back()->with('success', $mensagem);
    }

    public function registrarPadrao()
    {
        ConsumoAgua::adicionar(250);

        $progresso = ConsumoAgua::progressoHoje();

        return redirect()->back()
            ->with('success', '+250ml registrados! 💧 ('.$progresso['percentual'].'% da meta)');
    }
}

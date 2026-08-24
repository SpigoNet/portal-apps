<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendamentoController
{
    public function index(): View
    {
        $agendamentos = Agendamento::query()
            ->orderByDesc('ativo')
            ->orderBy('proxima_execucao')
            ->get();

        $personas = Persona::query()->orderBy('slug')->get();

        return view('Pidgey::agendamentos.index', compact('agendamentos', 'personas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'persona_slug' => 'required|string',
            'mensagem' => 'required|string',
            'canal' => 'sometimes|in:telegram,whatsapp,email',
            'interpretar' => 'sometimes|boolean',
            'frequencia' => 'required|in:una_vez,diario,semanal',
            'hora' => 'sometimes|nullable|string',
            'dia_semana' => 'sometimes|nullable|integer|min:0|max:6',
        ]);

        $persona = Persona::query()->where('slug', $data['persona_slug'])->firstOrFail();

        $agendamento = new Agendamento;
        $agendamento->user_id = auth()->id();
        $agendamento->persona_slug = $persona->slug;
        $agendamento->canal = $data['canal'] ?? $persona->canal ?? 'telegram';
        $agendamento->mensagem = $data['mensagem'];
        $agendamento->interpretar = ! empty($data['interpretar']);
        $agendamento->frequencia = $data['frequencia'];
        $agendamento->hora = $data['hora'] ?? '09:00';
        $agendamento->dia_semana = $data['frequencia'] === 'semanal'
            ? (int) ($data['dia_semana'] ?? now()->dayOfWeek)
            : null;
        $agendamento->ativo = true;

        if ($data['frequencia'] === 'una_vez') {
            $agendamento->proxima_execucao = now()->setTimeFromTimeString($agendamento->hora);
            if ($agendamento->proxima_execucao->lte(now())) {
                $agendamento->proxima_execucao->addDay();
            }
        } else {
            $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
        }

        $agendamento->save();

        return redirect()->route('pidgey.agendamentos.index')
            ->with('success', 'Agendamento criado com sucesso.');
    }

    public function destroy(Agendamento $agendamento): RedirectResponse
    {
        $agendamento->delete();

        return redirect()->route('pidgey.agendamentos.index')
            ->with('success', 'Agendamento removido.');
    }

    public function toggle(Agendamento $agendamento): RedirectResponse
    {
        $agendamento->ativo = ! $agendamento->ativo;

        if ($agendamento->ativo && ! $agendamento->proxima_execucao) {
            $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
        }

        $agendamento->save();

        return redirect()->route('pidgey.agendamentos.index')
            ->with('success', $agendamento->ativo ? 'Agendamento ativado.' : 'Agendamento pausado.');
    }
}

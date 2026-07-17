<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\Agendamento;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Services\EvolutionApiService;
use App\Modules\Alfred\Services\MensagemPersonaService;
use Illuminate\Http\Request;

class AgendamentoController
{
    public function index()
    {
        $agendamentos = Agendamento::with('persona')->get();

        return view('Alfred::admin.agendamentos.index', ['agendamentos' => $agendamentos]);
    }

    public function create()
    {
        $personas = Persona::where('active', true)->get();

        return view('Alfred::admin.agendamentos.create', ['personas' => $personas]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|exists:alfred_personas,id',
            'mensagem' => 'required|string|max:2000',
            'intervalo_minutos' => 'required|integer|min:10|max:1440',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'dias_semana' => 'required|array|min:1',
            'dias_semana.*' => 'integer|between:1,7',
        ]);

        Agendamento::create($validated);

        return redirect()->route('alfred.admin.agendamentos.index')->with('success', 'Agendamento criado');
    }

    public function edit(Agendamento $agendamento)
    {
        $personas = Persona::where('active', true)->get();

        return view('Alfred::admin.agendamentos.edit', [
            'agendamento' => $agendamento,
            'personas' => $personas,
        ]);
    }

    public function update(Request $request, Agendamento $agendamento)
    {
        $validated = $request->validate([
            'persona_id' => 'required|exists:alfred_personas,id',
            'mensagem' => 'required|string|max:2000',
            'intervalo_minutos' => 'required|integer|min:10|max:1440',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'dias_semana' => 'required|array|min:1',
            'dias_semana.*' => 'integer|between:1,7',
            'ativa' => 'nullable|boolean',
        ]);

        $validated['ativa'] = isset($validated['ativa']) && $validated['ativa'];

        $agendamento->update($validated);

        return redirect()->route('alfred.admin.agendamentos.index')->with('success', 'Agendamento atualizado');
    }

    public function destroy(Agendamento $agendamento)
    {
        $agendamento->delete();

        return redirect()->route('alfred.admin.agendamentos.index')->with('success', 'Agendamento removido');
    }

    public function toggle(Agendamento $agendamento)
    {
        $agendamento->update(['ativa' => ! $agendamento->ativa]);

        $status = $agendamento->ativa ? 'ativado' : 'desativado';

        return redirect()->back()->with('success', "Agendamento {$status}");
    }

    public function sendTest(Agendamento $agendamento, EvolutionApiService $evo, MensagemPersonaService $mensagemPersonaService)
    {
        $persona = $agendamento->persona;

        if (! $persona || ! $persona->whatsapp_group_jid) {
            return redirect()->back()->with('error', 'Persona sem grupo WhatsApp configurado');
        }

        $mensagem = $mensagemPersonaService->gerarMensagem($persona, (string) $agendamento->mensagem);

        $resultado = $evo->sendTextToGroup($persona->whatsapp_group_jid, $mensagem);

        if ($resultado['ok']) {
            return redirect()->back()->with('success', "Mensagem enviada via {$persona->name} (status {$resultado['status']})");
        }

        return redirect()->back()->with('error', "Falha ao enviar: {$resultado['error']}");
    }
}

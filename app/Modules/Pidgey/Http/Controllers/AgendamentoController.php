<?php

namespace App\Modules\Pidgey\Http\Controllers;

use App\Modules\Admin\Services\AiProviderService;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use App\Modules\Pidgey\Services\EnvioService;
use App\Modules\Pidgey\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendamentoController
{
    public function index(TelegramBotService $botService): View
    {
        $agendamentos = Agendamento::query()
            ->orderByDesc('ativo')
            ->orderBy('proxima_execucao')
            ->with('conteudosDinamicos')
            ->get();

        $grupos = $agendamentos->groupBy('persona_slug');

        $personas = Persona::query()->orderBy('slug')->get();
        $conteudos = ConteudoDinamico::query()->orderBy('nome')->get();

        $modelos = (new AiProviderService)->getActiveModels()
            ->filter(fn ($m) => in_array('text', $m->input_types ?? [], true)
                && in_array('text', $m->output_types ?? [], true))
            ->values();

        $personasPorSlug = $personas->keyBy('slug');
        $fotos = [];
        foreach ($grupos->keys() as $slug) {
            $persona = $personasPorSlug->get($slug);
            if ($persona && $persona->canal === 'telegram' && ! empty($persona->telegram_token)) {
                $fotos[$slug] = $botService->fotoUrl($persona->telegram_token);
            }
        }

        return view('Pidgey::agendamentos.index', compact('grupos', 'personas', 'conteudos', 'modelos', 'fotos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'persona_slug' => 'required|string',
            'mensagem' => 'required|string',
            'canal' => 'sometimes|in:telegram,whatsapp,email',
            'interpretar' => 'sometimes|boolean',
            'conteudos_dinamico' => 'sometimes|array',
            'conteudos_dinamico.*' => 'exists:pidgey_conteudos_dinamicos,id',
            'ai_model_id' => 'sometimes|nullable|exists:ai_modelos,id',
            'frequencia' => 'required|in:una_vez,diario,semanal,intervalo',
            'hora' => 'sometimes|nullable|string',
            'dia_semana' => 'sometimes|nullable|integer|min:0|max:6',
            'dias_semana' => 'sometimes|nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'intervalo_minutos' => 'sometimes|nullable|integer|min:10|max:1440',
            'hora_inicio' => 'sometimes|nullable|string',
            'hora_fim' => 'sometimes|nullable|string',
            'data_inicio' => 'sometimes|nullable|date',
            'data_fim' => 'sometimes|nullable|date|after_or_equal:data_inicio',
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
        $agendamento->dias_semana = in_array($data['frequencia'], ['diario', 'intervalo'])
            ? array_map('intval', $data['dias_semana'] ?? [])
            : null;
        $agendamento->intervalo_minutos = $data['frequencia'] === 'intervalo'
            ? (int) ($data['intervalo_minutos'] ?? 120)
            : null;
        $agendamento->hora_inicio = $data['frequencia'] === 'intervalo'
            ? ($data['hora_inicio'] ?? '08:00')
            : null;
        $agendamento->hora_fim = $data['frequencia'] === 'intervalo'
            ? ($data['hora_fim'] ?? '22:00')
            : null;
        $agendamento->data_inicio = $data['data_inicio'] ?? null;
        $agendamento->data_fim = $data['data_fim'] ?? null;
        $agendamento->ai_model_id = $data['ai_model_id'] ?? null;
        $agendamento->ativo = true;

        if ($data['frequencia'] === 'una_vez') {
            $base = $agendamento->data_inicio ?? now();
            $agendamento->proxima_execucao = $base->copy()->setTimeFromTimeString($agendamento->hora);
            if ($agendamento->proxima_execucao->lte(now())) {
                $agendamento->proxima_execucao->addDay();
            }
        } else {
            $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
        }

        $agendamento->save();

        $agendamento->conteudosDinamicos()->sync($data['conteudos_dinamico'] ?? []);

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

    public function enviarAgora(Agendamento $agendamento, EnvioService $envio): RedirectResponse
    {
        $resultado = $envio->enviarAgendamento($agendamento);

        if (! $resultado['ok']) {
            return redirect()->route('pidgey.agendamentos.index')
                ->with('error', "Falha ao enviar: {$resultado['error']}");
        }

        $agendamento->atualizarAposEnvio();

        return redirect()->route('pidgey.agendamentos.index')
            ->with('success', "Mensagem enviada via {$agendamento->persona_slug} (status {$resultado['status']}).");
    }
}

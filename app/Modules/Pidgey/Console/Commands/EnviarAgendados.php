<?php

namespace App\Modules\Pidgey\Console\Commands;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\EnvioService;
use Illuminate\Console\Command;

class EnviarAgendados extends Command
{
    protected $signature = 'pidgey:enviar-agendados';

    protected $description = 'Dispara as mensagens agendadas do Pidgey cuja próxima execução já chegou';

    public function handle(EnvioService $envio): int
    {
        $agendamentos = Agendamento::query()
            ->where('ativo', true)
            ->whereNotNull('proxima_execucao')
            ->where('proxima_execucao', '<=', now())
            ->with('user')
            ->get();

        if ($agendamentos->isEmpty()) {
            $this->info('Nenhum agendamento na hora de enviar.');

            return 0;
        }

        $enviados = 0;
        $erros = 0;

        foreach ($agendamentos as $agendamento) {
            $persona = Persona::query()->where('slug', $agendamento->persona_slug)->first();

            if (! $persona) {
                $this->warn("Agendamento #{$agendamento->id}: persona '{$agendamento->persona_slug}' não encontrada. Pulando.");
                $erros++;

                continue;
            }

            $resultado = $envio->enviar(
                $persona,
                $agendamento->mensagem,
                $agendamento->canal,
                (bool) $agendamento->interpretar
            );

            if (! $resultado['ok']) {
                $this->error("Agendamento #{$agendamento->id} falhou: {$resultado['error']}");
                $erros++;

                continue;
            }

            if ($agendamento->frequencia === 'una_vez') {
                $agendamento->ativo = false;
                $agendamento->proxima_execucao = null;
            } else {
                $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
            }

            $agendamento->save();

            $this->info("Agendamento #{$agendamento->id} enviado via {$persona->slug} (status {$resultado['status']})");
            $enviados++;
        }

        $this->newLine();
        $this->info("Resumo: {$enviados} enviados, {$erros} erros.");

        return 0;
    }
}

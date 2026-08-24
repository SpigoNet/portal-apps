<?php

namespace App\Modules\Alfred\Console\Commands;

use App\Modules\Alfred\Models\Agendamento;
use App\Modules\Alfred\Services\MensagemPersonaService;
use App\Modules\Alfred\Services\PersonaEnvioService;
use Illuminate\Console\Command;

class EnviarMensagensAgendadas extends Command
{
    protected $signature = 'alfred:enviar-mensagens-agendadas';

    protected $description = 'Envia mensagens agendadas (WhatsApp ou Telegram) pelas personas configuradas';

    public function handle(PersonaEnvioService $envio, MensagemPersonaService $mensagemPersonaService): int
    {
        $agendamentos = Agendamento::with('persona')
            ->where('ativa', true)
            ->get();

        if ($agendamentos->isEmpty()) {
            $this->info('Nenhum agendamento ativo.');

            return 0;
        }

        $enviados = 0;
        $erros = 0;

        foreach ($agendamentos as $agendamento) {
            if (! $agendamento->deveEnviarAgora()) {
                continue;
            }

            $persona = $agendamento->persona;

            if (! $persona) {
                $this->warn("Agendamento #{$agendamento->id}: sem persona. Pulando.");
                $erros++;

                continue;
            }

            $mensagem = $mensagemPersonaService->gerarMensagem($persona, (string) $agendamento->mensagem);

            $resultado = $envio->enviar($persona, $mensagem);

            if ($resultado['ok']) {
                $agendamento->marcarEnviado();
                $this->info("Agendamento #{$agendamento->id} enviado via {$persona->name} ({$persona->canal}, status {$resultado['status']})");
                $enviados++;
            } else {
                $this->error("Agendamento #{$agendamento->id} falhou: {$resultado['error']}");
                $erros++;
            }
        }

        $this->newLine();
        $this->info("Resumo: {$enviados} enviados, {$erros} erros.");

        return 0;
    }
}

<?php

namespace App\Modules\Alfred\Console\Commands;

use App\Modules\Alfred\Models\Agendamento;
use App\Modules\Alfred\Services\EvolutionApiService;
use App\Modules\Alfred\Services\MensagemPersonaService;
use Illuminate\Console\Command;

class EnviarMensagensAgendadas extends Command
{
    protected $signature = 'alfred:enviar-mensagens-agendadas';

    protected $description = 'Envia mensagens agendadas via WhatsApp pelas personas configuradas';

    public function handle(EvolutionApiService $evo, MensagemPersonaService $mensagemPersonaService): int
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

            if (! $persona || ! $persona->whatsapp_group_jid) {
                $this->warn("Agendamento #{$agendamento->id}: persona sem grupo WhatsApp. Pulando.");
                $erros++;

                continue;
            }

            $mensagem = $mensagemPersonaService->gerarMensagem($persona, (string) $agendamento->mensagem);

            $resultado = $evo->sendTextToGroup($persona->whatsapp_group_jid, $mensagem);

            if ($resultado['ok']) {
                $agendamento->marcarEnviado();
                $this->info("Agendamento #{$agendamento->id} enviado via {$persona->name} (status {$resultado['status']})");
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

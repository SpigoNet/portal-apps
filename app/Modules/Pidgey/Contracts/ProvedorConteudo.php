<?php

namespace App\Modules\Pidgey\Contracts;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;

/**
 * Conteúdo dinâmico de um módulo que monta a mensagem inteira do agendamento (dados determinísticos,
 * sem passar pela IA). É enviado ao chat do Telegram vinculado ao dono do agendamento.
 */
interface ProvedorConteudo
{
    /**
     * Valor de `ConteudoDinamico::tipo` atendido por este provedor.
     */
    public function tipo(): string;

    /**
     * Texto (HTML do Telegram) a enviar agora, ou null quando não há nada a dizer.
     */
    public function mensagem(Agendamento $agendamento, Persona $persona): ?string;
}

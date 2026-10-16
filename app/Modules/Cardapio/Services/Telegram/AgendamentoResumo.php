<?php

namespace App\Modules\Cardapio\Services\Telegram;

use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use App\Modules\Pidgey\Models\TelegramVinculo;
use Illuminate\Support\Facades\Schema;

/**
 * Garante que quem tem Telegram vinculado e participa de uma casa tenha, no Pidgey, o agendamento diário
 * do resumo do Cardápio (editável ou desativável em /pidgey). Idempotente: nunca duplica.
 */
class AgendamentoResumo
{
    public const HORA = '07:00';

    public function garantir(int $userId): ?Agendamento
    {
        // O Pidgey pode não estar migrado (ex.: testes isolados do Cardápio).
        if (! Schema::hasTable('pidgey_agendamentos') || ! Schema::hasTable('pidgey_conteudos_dinamicos') || ! Schema::hasTable('pidgey_telegram_vinculos')) {
            return null;
        }

        if (! TelegramVinculo::where('user_id', $userId)->exists()
            || ! Pessoa::where('user_id', $userId)->where('ativo', true)->exists()) {
            return null;
        }

        $conteudo = ConteudoDinamico::firstOrCreate(
            ['tipo' => ResumoDiarioProvedor::TIPO],
            ['nome' => 'Resumo do Cardápio (Sanji)', 'conteudo' => null, 'ativo' => true, 'sistema' => true],
        );

        $existente = Agendamento::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('persona_slug', SanjiTelegram::PERSONA)
            ->whereHas('conteudosDinamicos', fn ($q) => $q->where('pidgey_conteudos_dinamicos.id', $conteudo->id))
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        $agendamento = new Agendamento([
            'user_id' => $userId,
            'persona_slug' => SanjiTelegram::PERSONA,
            'canal' => 'telegram',
            'mensagem' => 'Resumo do dia da cozinha',
            'interpretar' => false,
            'frequencia' => 'diario',
            'hora' => self::HORA,
            'ativo' => true,
        ]);
        $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
        $agendamento->save();
        $agendamento->conteudosDinamicos()->attach($conteudo->id);

        return $agendamento;
    }
}

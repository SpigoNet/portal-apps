<?php

use App\Modules\Cardapio\Services\Telegram\AgendamentoResumo;
use App\Modules\Pidgey\Models\TelegramVinculo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * O resumo das 07:00 deixa de ser um comando próprio e passa a ser um agendamento do Pidgey, criado para
 * quem já tem Telegram vinculado (os próximos nascem ao vincular ou ao entrar numa casa).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pidgey_agendamentos', 'pidgey_conteudos_dinamicos', 'pidgey_telegram_vinculos', 'cardapio_pessoas'] as $tabela) {
            if (! Schema::hasTable($tabela)) {
                return;
            }
        }

        $servico = app(AgendamentoResumo::class);

        TelegramVinculo::query()->pluck('user_id')->each(fn ($userId) => $servico->garantir((int) $userId));
    }

    public function down(): void {}
};

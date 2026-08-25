<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->date('data_inicio')->nullable(); // início do período do agendamento
            $table->date('data_fim')->nullable(); // fim do período; após essa data, o agendamento é encerrado
        });
    }

    public function down(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->dropColumn(['data_inicio', 'data_fim']);
        });
    }
};

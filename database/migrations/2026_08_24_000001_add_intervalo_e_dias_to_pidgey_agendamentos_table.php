<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->unsignedSmallInteger('intervalo_minutos')->nullable(); // para 'intervalo'
            $table->string('hora_inicio', 5)->nullable(); // H:i para 'intervalo'
            $table->string('hora_fim', 5)->nullable(); // H:i para 'intervalo'
            $table->json('dias_semana')->nullable(); // [0..6] para 'diario' e 'intervalo'; vazio = todos os dias
        });
    }

    public function down(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->dropColumn(['intervalo_minutos', 'hora_inicio', 'hora_fim', 'dias_semana']);
        });
    }
};

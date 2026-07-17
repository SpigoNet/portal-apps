<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_mensagens_agendadas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('persona_id')->constrained('alfred_personas')->cascadeOnDelete();
            $table->text('mensagem');
            $table->unsignedSmallInteger('intervalo_minutos')->default(120);
            $table->string('hora_inicio', 5)->default('08:00');
            $table->string('hora_fim', 5)->default('22:00');
            $table->json('dias_semana')->default('[0,1,2,3,4,5,6]');
            $table->boolean('ativa')->default(true);
            $table->timestamp('ultimo_envio_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_mensagens_agendadas');
    }
};

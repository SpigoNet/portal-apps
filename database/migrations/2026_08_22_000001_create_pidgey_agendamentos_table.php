<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pidgey_agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('persona_slug');
            $table->string('canal')->default('telegram');
            $table->text('mensagem');
            $table->boolean('interpretar')->default(false);
            $table->string('frequencia')->default('una_vez'); // una_vez | diario | semanal
            $table->string('hora')->nullable(); // H:i para diario/semanal
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 0=dom .. 6=sab (semanal)
            $table->timestamp('proxima_execucao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pidgey_agendamentos');
    }
};

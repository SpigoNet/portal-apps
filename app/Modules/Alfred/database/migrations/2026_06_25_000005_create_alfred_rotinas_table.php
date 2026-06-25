<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_rotinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('tipo_recorrencia', ['diaria', 'semanal', 'mensal', 'unica']);
            $table->json('config_recorrencia')->nullable();
            $table->time('horario_sugerido')->nullable();
            $table->enum('categoria', ['saude', 'trabalho', 'lazer', 'financeiro', 'familia', 'estudo', 'outro'])->default('outro');
            $table->boolean('ativa')->default(true);
            $table->timestamp('ultima_execucao')->nullable();
            $table->integer('prioridade')->default(2);
            $table->timestamps();
        });

        Schema::create('alfred_rotina_execucoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rotina_id')->constrained('alfred_rotinas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('data_execucao');
            $table->time('hora_execucao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['rotina_id', 'data_execucao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_rotina_execucoes');
        Schema::dropIfExists('alfred_rotinas');
    }
};

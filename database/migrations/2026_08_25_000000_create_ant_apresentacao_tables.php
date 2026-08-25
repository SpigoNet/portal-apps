<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Apresentações (atividade com comportamento próprio)
        Schema::create('ant_apresentacoes', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('nome', 255);
            $table->text('descricao')->nullable();
            $table->foreignId('peso_apresentacao_id')->nullable()->constrained('ant_pesos')->onDelete('set null');
            $table->foreignId('peso_participacao_id')->nullable()->constrained('ant_pesos')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Agendamentos (datas de apresentação com tema)
        Schema::create('ant_apresentacao_agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apresentacao_id')->constrained('ant_apresentacoes')->onDelete('cascade');
            $table->date('data');
            $table->string('tema', 255);
            $table->timestamps();
            $table->index(['apresentacao_id', 'data']);
        });

        // 3. Apresentadores (alunos que apresentam em cada agendamento - avaliação individual)
        Schema::create('ant_apresentacao_apresentadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('ant_apresentacao_agendamentos')->onDelete('cascade');
            $table->string('aluno_ra', 13);
            $table->double('nota')->nullable()->comment('Nota da avaliação da apresentação (0-10)');
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['agendamento_id', 'aluno_ra']);
            $table->foreign('aluno_ra')->references('ra')->on('ant_alunos')->onDelete('cascade');
        });

        // 4. Entregas da apresentação (material enviado pelo apresentador)
        Schema::create('ant_apresentacao_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apresentador_id')->constrained('ant_apresentacao_apresentadores')->onDelete('cascade');
            $table->text('arquivos')->nullable()->comment('JSON com caminhos ou links');
            $table->text('comentario_aluno')->nullable();
            $table->datetime('data_entrega')->nullable();
            $table->timestamps();

            $table->unique(['apresentador_id']);
        });

        // 5. Estrelas de participação (1 linha por estrela concedida)
        Schema::create('ant_estrelas', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('aluno_ra', 13);
            $table->foreignId('apresentacao_id')->constrained('ant_apresentacoes')->onDelete('cascade');
            $table->foreignId('agendamento_id')->constrained('ant_apresentacao_agendamentos')->onDelete('cascade');
            $table->timestamps();

            $table->foreign('aluno_ra')->references('ra')->on('ant_alunos')->onDelete('cascade');
            $table->index(['materia_id', 'semestre', 'aluno_ra']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ant_estrelas');
        Schema::dropIfExists('ant_apresentacao_entregas');
        Schema::dropIfExists('ant_apresentacao_apresentadores');
        Schema::dropIfExists('ant_apresentacao_agendamentos');
        Schema::dropIfExists('ant_apresentacoes');
    }
};

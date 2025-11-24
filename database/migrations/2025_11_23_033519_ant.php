<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela: ant_configuracoes
        Schema::create('ant_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('semestre_atual', 6);
            $table->timestamps();
        });

        // Tabela: ant_tipos_trabalho
        Schema::create('ant_tipos_trabalho', function (Blueprint $table) {
            $table->id();
            $table->string('descricao', 255);
            $table->string('arquivos', 255)->comment('Tipos de arquivos permitidos (extensões)');
            $table->timestamps();
        });

        // Tabela: ant_materias
        Schema::create('ant_materias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('nome_curto', 100)->unique();
            $table->timestamps();
        });

        // Tabela: ant_professores
        Schema::create('ant_professores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 250);
            $table->enum('titulacao', ['Esp.', 'Me.', 'Dr.', ''])->default('');
            $table->string('login', 250)->unique();
            $table->string('senha', 255); // Aumentado para suportar Hash do Laravel
            $table->timestamps();
        });

        // Tabela: ant_alunos
        Schema::create('ant_alunos', function (Blueprint $table) {
            $table->id();
            $table->string('ra', 13)->unique(); // RA mantido como campo único, mas ID é a PK
            $table->string('nome', 255);
            $table->timestamps();
        });

        // Tabela: ant_questoes
        Schema::create('ant_questoes', function (Blueprint $table) {
            $table->id();
            $table->text('enunciado');
            $table->string('database_name', 100);
            $table->text('query_correta');
            $table->boolean('dissertativa')->default(false);
            $table->boolean('multipla_escolha')->default(false);
            $table->timestamps();
        });

        // Tabela: ant_alternativas
        Schema::create('ant_alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->text('texto');
            $table->boolean('correta');
            $table->timestamps();
        });

        // Tabela: ant_links
        Schema::create('ant_links', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 100);
            $table->string('nome', 100);
            $table->text('link')->nullable();
            $table->boolean('is_video')->default(false);
            $table->timestamps();
        });

        // Tabela: ant_professor_materia
        Schema::create('ant_professor_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('ant_professores')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('semestre', 6);
            $table->timestamps();
        });

        // Tabela: ant_aluno_materia
        Schema::create('ant_aluno_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('ant_alunos')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('semestre', 6);
            $table->unique(['aluno_id', 'materia_id', 'semestre']);
            $table->timestamps();
        });

        // Tabela: ant_pesos
        Schema::create('ant_pesos', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('grupo', 100);
            $table->double('valor');
            $table->timestamps();
        });

        // Tabela: ant_trabalhos
        Schema::create('ant_trabalhos', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->string('nome', 255);
            $table->text('descricao');
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->foreignId('tipo_trabalho_id')->constrained('ant_tipos_trabalho');
            $table->date('prazo');
            $table->integer('maximo_alunos');
            $table->foreignId('peso_id')->constrained('ant_pesos');
            $table->timestamps();
        });

        // Tabela: ant_provas
        Schema::create('ant_provas', function (Blueprint $table) {
            $table->id();
            $table->text('descricao');
            $table->boolean('disponivel')->default(false);
            $table->foreignId('trabalho_id')->nullable()->constrained('ant_trabalhos')->onDelete('set null');
            $table->timestamps();
        });

        // Tabela: ant_prova_questoes
        Schema::create('ant_prova_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->constrained('ant_provas')->onDelete('cascade');
            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->integer('ordem');
            $table->unique(['prova_id', 'questao_id']);
            $table->timestamps();
        });

        // Tabela: ant_entregas
        Schema::create('ant_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabalho_id')->constrained('ant_trabalhos')->onDelete('cascade');
            $table->foreignId('aluno_id')->constrained('ant_alunos')->onDelete('cascade');
            $table->text('arquivos')->nullable();
            $table->text('comentario_aluno')->nullable();
            $table->dateTime('data_entrega');
            $table->double('nota')->nullable();
            $table->text('comentario_professor')->nullable();
            $table->unique(['trabalho_id', 'aluno_id']);
            $table->timestamps();
        });

        // Tabela: ant_prova_respostas
        Schema::create('ant_prova_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->constrained('ant_provas')->onDelete('cascade');
            $table->foreignId('aluno_id')->constrained('ant_alunos')->onDelete('cascade');
            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->text('resposta')->nullable();
            $table->string('pre_avaliacao', 200)->nullable();
            $table->integer('pontuacao')->nullable();
            $table->timestamp('quando')->useCurrent();
            $table->unique(['prova_id', 'aluno_id', 'questao_id'], 'ant_prova_resp_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ant_prova_respostas');
        Schema::dropIfExists('ant_entregas');
        Schema::dropIfExists('ant_prova_questoes');
        Schema::dropIfExists('ant_provas');
        Schema::dropIfExists('ant_trabalhos');
        Schema::dropIfExists('ant_pesos');
        Schema::dropIfExists('ant_aluno_materia');
        Schema::dropIfExists('ant_professor_materia');
        Schema::dropIfExists('ant_links');
        Schema::dropIfExists('ant_alternativas');
        Schema::dropIfExists('ant_questoes');
        Schema::dropIfExists('ant_alunos');
        Schema::dropIfExists('ant_professores');
        Schema::dropIfExists('ant_materias');
        Schema::dropIfExists('ant_tipos_trabalho');
        Schema::dropIfExists('ant_configuracoes');
    }
};

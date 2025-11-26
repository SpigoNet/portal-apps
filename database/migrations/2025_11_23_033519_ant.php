<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Configurações Gerais
        Schema::create('ant_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('semestre_atual', 6)->comment('Ex: 2025-2');
            $table->timestamps();
        });

        // 2. Tipos de Trabalho (Link, PDF, ZIP...)
        Schema::create('ant_tipos_trabalho', function (Blueprint $table) {
            $table->id();
            $table->string('descricao', 255);
            $table->string('arquivos', 255)->comment('Extensões permitidas ex: pdf|zip|link');
            $table->timestamps();
        });

        // 3. Matérias
        Schema::create('ant_materias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('nome_curto', 100)->unique();
            $table->timestamps();
        });

        // 4. Alunos (Tabela Mestra dos RAs)
        Schema::create('ant_alunos', function (Blueprint $table) {
            $table->id();
            // O RA é a chave de negócio usada para relacionamentos externos
            $table->string('ra', 13)->unique();
            $table->string('nome', 255);
            // User ID vincula ao login do Laravel (pode ser nulo se aluno ainda não criou conta)
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 5. Professores (Vínculo User -> Matéria)
        // Professores precisam de login, então usamos user_id
        Schema::create('ant_professor_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('semestre', 6);
            $table->timestamps();
        });

        // 6. Matrículas (VÍNCULO PELO RA)
        // Permite importar matrículas sem que o aluno tenha usuário no sistema
        Schema::create('ant_aluno_materia', function (Blueprint $table) {
            $table->id();

            $table->string('aluno_ra', 13);
            $table->foreign('aluno_ra')->references('ra')->on('ant_alunos')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('semestre', 6);

            $table->unique(['aluno_ra', 'materia_id', 'semestre']);
            $table->timestamps();
        });

        // 7. Pesos de Notas
        Schema::create('ant_pesos', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->string('grupo', 100)->comment('Ex: P1, Trabalhos');
            $table->double('valor');
            $table->timestamps();
        });

        // 8. Trabalhos
        Schema::create('ant_trabalhos', function (Blueprint $table) {
            $table->id();
            $table->string('semestre', 6);
            $table->string('nome', 255);
            $table->text('descricao');
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->foreignId('tipo_trabalho_id')->constrained('ant_tipos_trabalho');
            $table->date('prazo');
            $table->integer('maximo_alunos')->default(1);
            $table->foreignId('peso_id')->constrained('ant_pesos');
            $table->timestamps();
        });

        // 9. Entregas (VÍNCULO PELO RA)
        Schema::create('ant_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabalho_id')->constrained('ant_trabalhos')->onDelete('cascade');

            $table->string('aluno_ra', 13);
            $table->foreign('aluno_ra')->references('ra')->on('ant_alunos')->onDelete('cascade')->onUpdate('cascade');

            $table->text('arquivos')->nullable()->comment('JSON com caminhos ou link');
            $table->text('comentario_aluno')->nullable();
            $table->dateTime('data_entrega');
            $table->double('nota')->nullable();
            $table->text('comentario_professor')->nullable();

            $table->unique(['trabalho_id', 'aluno_ra']);
            $table->timestamps();
        });

        // 10. Questões (Banco de Questões)
        Schema::create('ant_questoes', function (Blueprint $table) {
            $table->id();
            $table->text('enunciado');
            $table->string('database_name', 100)->nullable();
            $table->text('query_correta')->nullable();
            $table->boolean('dissertativa')->default(false);
            $table->boolean('multipla_escolha')->default(false);
            $table->timestamps();
        });

        // 11. Alternativas
        Schema::create('ant_alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->text('texto');
            $table->boolean('correta');
            $table->timestamps();
        });

        // 12. Provas
        Schema::create('ant_provas', function (Blueprint $table) {
            $table->id();
            $table->text('descricao');
            $table->boolean('disponivel')->default(false);
            $table->foreignId('trabalho_id')->nullable()->constrained('ant_trabalhos')->onDelete('set null');
            $table->timestamps();
        });

        // 13. Questões da Prova
        Schema::create('ant_prova_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->constrained('ant_provas')->onDelete('cascade');
            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->integer('ordem');
            $table->unique(['prova_id', 'questao_id']);
            $table->timestamps();
        });

        // 14. Respostas da Prova (VÍNCULO PELO RA)
        Schema::create('ant_prova_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->constrained('ant_provas')->onDelete('cascade');

            $table->string('aluno_ra', 13);
            $table->foreign('aluno_ra')->references('ra')->on('ant_alunos')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('questao_id')->constrained('ant_questoes')->onDelete('cascade');
            $table->text('resposta')->nullable();
            $table->string('pre_avaliacao', 200)->nullable();
            $table->integer('pontuacao')->nullable();
            $table->timestamp('quando')->useCurrent();

            $table->unique(['prova_id', 'aluno_ra', 'questao_id'], 'ant_prova_resp_unique');
            $table->timestamps();
        });

        // 15. Links Úteis
        Schema::create('ant_links', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 100);
            $table->string('nome', 100);
            $table->text('link')->nullable();
            $table->boolean('is_video')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Ordem reversa exata
        Schema::dropIfExists('ant_links');
        Schema::dropIfExists('ant_prova_respostas');
        Schema::dropIfExists('ant_prova_questoes');
        Schema::dropIfExists('ant_provas');
        Schema::dropIfExists('ant_alternativas');
        Schema::dropIfExists('ant_questoes');
        Schema::dropIfExists('ant_entregas');
        Schema::dropIfExists('ant_trabalhos');
        Schema::dropIfExists('ant_pesos');
        Schema::dropIfExists('ant_aluno_materia');
        Schema::dropIfExists('ant_professor_materia');
        Schema::dropIfExists('ant_alunos');
        Schema::dropIfExists('ant_materias');
        Schema::dropIfExists('ant_tipos_trabalho');
        Schema::dropIfExists('ant_configuracoes');
    }
};

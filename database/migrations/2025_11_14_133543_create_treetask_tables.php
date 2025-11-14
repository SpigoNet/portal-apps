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
        // 1. Tabela de Projetos (Nível 1)
        Schema::create('treetask_projetos', function (Blueprint $table) {
            $table->bigIncrements('id_projeto');
            $table->string('nome');
            $table->text('descricao')->comment('Suporta HTML');
            $table->string('status')->default('Planejamento');

            $table->unsignedBigInteger('id_user_owner'); // FK para users.id

            $table->timestamp('data_inicio')->nullable();
            $table->timestamp('data_prevista_termino')->nullable();
            $table->timestamp('data_conclusao_real')->nullable();
            $table->timestamps(); // created_at e updated_at

            // Definição da Chave Estrangeira
            $table->foreign('id_user_owner')->references('id')->on('users');
        });

        // 2. Tabela de Fases (Nível 2)
        Schema::create('treetask_fases', function (Blueprint $table) {
            $table->bigIncrements('id_fase');
            $table->unsignedBigInteger('id_projeto'); // FK para treetask_projetos

            $table->string('nome');
            $table->text('descricao')->nullable()->comment('Suporta HTML');
            $table->string('status')->default('A Fazer'); // Status calculado pela app
            $table->integer('ordem')->default(0);
            $table->timestamps();

            // Definição da Chave Estrangeira
            $table->foreign('id_projeto')
                ->references('id_projeto')
                ->on('treetask_projetos')
                ->onDelete('cascade'); // Se o projeto for deletado, as fases vão junto
        });

        // 3. Tabela de Tarefas (Nível 3)
        Schema::create('treetask_tarefas', function (Blueprint $table) {
            $table->bigIncrements('id_tarefa');
            $table->unsignedBigInteger('id_fase'); // FK para treetask_fases

            $table->string('titulo');
            $table->text('descricao')->nullable()->comment('Suporta HTML');
            $table->string('status')->default('A Fazer'); // Status manual

            $table->unsignedBigInteger('id_user_responsavel'); // FK para users.id

            $table->string('prioridade')->nullable();
            $table->timestamp('data_vencimento')->nullable();
            $table->decimal('estimativa_tempo', 8, 2)->nullable(); // Ex: 40.50 (horas)
            $table->timestamps();

            // Definições das Chaves Estrangeiras
            $table->foreign('id_fase')
                ->references('id_fase')
                ->on('treetask_fases')
                ->onDelete('cascade'); // Se a fase for deletada, as tarefas vão junto

            $table->foreign('id_user_responsavel')->references('id')->on('users');
        });

        // 4. Tabela de Dependências (Pivô Nível 3 para Nível 3)
        Schema::create('treetask_dependencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tarefa'); // A tarefa que depende
            $table->unsignedBigInteger('id_tarefa_depende_de'); // A tarefa pré-requisito

            // Definições das Chaves Estrangeiras
            $table->foreign('id_tarefa')
                ->references('id_tarefa')
                ->on('treetask_tarefas')
                ->onDelete('cascade');

            $table->foreign('id_tarefa_depende_de')
                ->references('id_tarefa')
                ->on('treetask_tarefas')
                ->onDelete('cascade');

            // Chave primária composta
            $table->primary(['id_tarefa', 'id_tarefa_depende_de'], 'treetask_dependencia_pk');
        });

        // 5. Tabela Central de Anexos
        Schema::create('treetask_anexos', function (Blueprint $table) {
            $table->bigIncrements('id_anexo');
            $table->unsignedBigInteger('id_user_upload'); // FK para users.id

            $table->string('nome_arquivo');
            $table->string('path_arquivo');
            $table->string('mime_type')->nullable();
            $table->integer('tamanho')->nullable()->comment('Em bytes');
            $table->timestamps();

            // Definição da Chave Estrangeira
            $table->foreign('id_user_upload')->references('id')->on('users');
        });

        // 6. Tabela Pivô Anexo <-> Projeto
        Schema::create('treetask_anexo_projeto', function (Blueprint $table) {
            $table->unsignedBigInteger('id_anexo');
            $table->unsignedBigInteger('id_projeto');

            // Definições das Chaves Estrangeiras
            $table->foreign('id_anexo')
                ->references('id_anexo')
                ->on('treetask_anexos')
                ->onDelete('cascade');

            $table->foreign('id_projeto')
                ->references('id_projeto')
                ->on('treetask_projetos')
                ->onDelete('cascade');

            // Chave primária composta
            $table->primary(['id_anexo', 'id_projeto']);
        });

        // 7. Tabela Pivô Anexo <-> Fase
        Schema::create('treetask_anexo_fase', function (Blueprint $table) {
            $table->unsignedBigInteger('id_anexo');
            $table->unsignedBigInteger('id_fase');

            // Definições das Chaves Estrangeiras
            $table->foreign('id_anexo')
                ->references('id_anexo')
                ->on('treetask_anexos')
                ->onDelete('cascade');

            $table->foreign('id_fase')
                ->references('id_fase')
                ->on('treetask_fases')
                ->onDelete('cascade');

            // Chave primária composta
            $table->primary(['id_anexo', 'id_fase']);
        });

        // 8. Tabela Pivô Anexo <-> Tarefa
        Schema::create('treetask_anexo_tarefa', function (Blueprint $table) {
            $table->unsignedBigInteger('id_anexo');
            $table->unsignedBigInteger('id_tarefa');

            // Definições das Chaves Estrangeiras
            $table->foreign('id_anexo')
                ->references('id_anexo')
                ->on('treetask_anexos')
                ->onDelete('cascade');

            $table->foreign('id_tarefa')
                ->references('id_tarefa')
                ->on('treetask_tarefas')
                ->onDelete('cascade');

            // Chave primária composta
            $table->primary(['id_anexo', 'id_tarefa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Desativa a verificação de chaves estrangeiras para permitir
        // apagar as tabelas em qualquer ordem de forma segura.
        Schema::disableForeignKeyConstraints();

        // Apaga as tabelas na ordem inversa de dependência (pivôs primeiro)
        Schema::dropIfExists('treetask_anexo_tarefa');
        Schema::dropIfExists('treetask_anexo_fase');
        Schema::dropIfExists('treetask_anexo_projeto');
        Schema::dropIfExists('treetask_anexos');
        Schema::dropIfExists('treetask_dependencias');
        Schema::dropIfExists('treetask_tarefas');
        Schema::dropIfExists('treetask_fases');
        Schema::dropIfExists('treetask_projetos');

        // Reativa a verificação
        Schema::enableForeignKeyConstraints();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela para os mapeamentos (DSpace 7/8 e legado)
        Schema::create('dspace_form_maps', function (Blueprint $table) {
            $table->id();
            $table->string('map_type')->comment('Tipo de mapeamento: handle ou entity-type');
            $table->string('map_key')->comment('O valor do handle ou do entity-type');
            $table->string('submission_name');
            $table->timestamps();
            $table->unique(['map_type', 'map_key']); // Garante que a combinação seja única
        });

        // Tabela para as listas de valores (vocabulários e dropdowns)
        Schema::create('dspace_value_pairs_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('dc_term')->nullable();
            $table->timestamps();
        });

        // Tabela para os pares de valor dentro de cada lista
        Schema::create('dspace_value_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('dspace_value_pairs_lists')->onDelete('cascade');
            $table->text('displayed_value');
            $table->text('stored_value')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabela para os formulários (ex: traditional-1, tcc-1)
        Schema::create('dspace_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Tabela para as linhas dentro de um formulário
        Schema::create('dspace_form_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('dspace_forms')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabela para os campos normais <field>
        Schema::create('dspace_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained('dspace_form_rows')->onDelete('cascade');
            $table->string('dc_schema');
            $table->string('dc_element');
            $table->string('dc_qualifier')->nullable();
            $table->boolean('repeatable')->default(false);
            $table->string('label', 512);
            $table->string('input_type');
            $table->text('hint');
            $table->string('required')->nullable();
            $table->string('style')->nullable();
            $table->string('vocabulary')->nullable();
            $table->boolean('vocabulary_closed')->default(false);
            $table->string('value_pairs_name')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabela para os campos de relação <relation-field>
        Schema::create('dspace_relation_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained('dspace_form_rows')->onDelete('cascade');
            $table->string('relationship_type');
            $table->string('search_configuration');
            $table->string('label', 512);
            $table->text('hint');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // --- TABELAS PARA item-submission.xml ---

        // Tabela para os processos de submissão <submission-process>
        Schema::create('submission_processes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Tabela para os passos de cada processo <step>
        Schema::create('submission_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_process_id')->constrained('submission_processes')->onDelete('cascade');
            $table->string('step_id'); // Ex: "collection", "tccpageoneForm", "upload"
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_steps');
        Schema::dropIfExists('submission_processes');
        Schema::dropIfExists('dspace_relation_fields');
        Schema::dropIfExists('dspace_form_fields');
        Schema::dropIfExists('dspace_form_rows');
        Schema::dropIfExists('dspace_forms');
        Schema::dropIfExists('dspace_value_pairs');
        Schema::dropIfExists('dspace_value_pairs_lists');
        Schema::dropIfExists('dspace_form_maps');
    }
};


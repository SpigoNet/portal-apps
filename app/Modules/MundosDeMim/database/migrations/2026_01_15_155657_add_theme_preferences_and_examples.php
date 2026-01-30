<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela Pivô: Preferências do Usuário (Quais temas ele quer?)
        Schema::create('mundos_de_mim_user_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('theme_id')->constrained('mundos_de_mim_themes')->onDelete('cascade');
            $table->boolean('is_enabled')->default(true); // Se ele desmarcar, vira false
            $table->timestamps();

            // Um usuário só pode ter um registro por tema
            $table->unique(['user_id', 'theme_id']);
        });

        // 2. Adicionar imagens de exemplo na tabela de Temas
        Schema::table('mundos_de_mim_themes', function (Blueprint $table) {
            // Imagem "Antes" (Input Padrão): Ex: Foto de um casal genérico
            $table->string('example_input_path')->nullable()->after('slug')
                ->comment('Caminho da imagem de referência (ex: casal.jpg)');

            // Descrição do Input: "Foto de Casal + Pet"
            $table->string('example_input_description')->nullable()->after('example_input_path');
        });

        // 3. Tabela de Exemplos "Depois" (Resultados)
        // Um tema pode ter várias imagens de resultado para mostrar variação
        Schema::create('mundos_de_mim_theme_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('mundos_de_mim_themes')->onDelete('cascade');
            $table->string('image_path')->comment('Caminho da arte gerada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mundos_de_mim_theme_examples');
        Schema::dropIfExists('mundos_de_mim_user_themes');

        Schema::table('mundos_de_mim_themes', function (Blueprint $table) {
            $table->dropColumn(['example_input_path', 'example_input_description']);
        });
    }
};

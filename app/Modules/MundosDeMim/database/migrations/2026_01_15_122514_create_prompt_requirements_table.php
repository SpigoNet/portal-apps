<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cria a tabela de requisitos
        Schema::create('mundos_de_mim_prompt_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('mundos_de_mim_prompts')->onDelete('cascade');

            // Ex: 'has_relationship', 'min_height', 'biometry_complete'
            $table->string('requirement_key');

            // Ex: 'Pet', 'Namorado(a)', '150', 'true'
            $table->string('requirement_value')->nullable();

            // Operador para comparação (opcional, default é igualdade)
            // Ex: '=', '>=', '!='
            $table->string('operator')->default('=');

            $table->timestamps();
        });

        // 2. Remove a coluna antiga (limpeza)
        Schema::table('mundos_de_mim_prompts', function (Blueprint $table) {
            $table->dropColumn('is_couple_prompt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mundos_de_mim_prompt_requirements');

        Schema::table('mundos_de_mim_prompts', function (Blueprint $table) {
            $table->boolean('is_couple_prompt')->default(false);
        });
    }
};

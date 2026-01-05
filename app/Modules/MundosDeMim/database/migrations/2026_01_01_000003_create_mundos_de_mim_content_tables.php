<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Estilos/Temas [cite: 35-39]
        Schema::create('mundos_de_mim_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('age_rating', ['kids', 'teen', 'adult'])->default('kids');
            $table->boolean('is_seasonal')->default(false);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        // 2. Tabela de Prompts (Textos Base) [cite: 40-43]
        Schema::create('mundos_de_mim_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->onDelete('cascade');
            $table->text('prompt_text');
            $table->boolean('is_couple_prompt')->default(false)->comment('Requer segunda pessoa');
            $table->timestamps();
        });

        // 3. Histórico de Gerações (Galeria) [cite: 68]
        Schema::create('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Referências opcionais (caso o tema seja deletado, mantemos o histórico visual)
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prompt_id')->nullable()->constrained()->nullOnDelete();

            $table->string('image_url'); // URL final da imagem (R2/S3)
            $table->text('final_prompt_used')->nullable()->comment('Prompt exato usado na geração');

            $table->date('reference_date')->index(); // Data da "entrega"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mundos_de_mim_daily_generations');
        Schema::dropIfExists('mundos_de_mim_prompts');
        Schema::dropIfExists('mundos_de_mim_themes');
    }
};

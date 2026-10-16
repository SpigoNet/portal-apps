<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_restricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_id')->constrained('cardapio_pessoas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->nullable()->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->string('tipo', 20); // alergia | intolerancia | preferencia | objetivo
            $table->string('descricao')->nullable(); // usado em objetivos (ex.: reduzir lactose)
            $table->timestamps();
        });

        Schema::create('cardapio_preferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_id')->constrained('cardapio_pessoas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->smallInteger('nivel'); // +5 muito gosta, +3 gosta, 0 neutro, -5 não gosta, -10 odeia
            $table->timestamps();

            $table->unique(['pessoa_id', 'ingrediente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_preferencias');
        Schema::dropIfExists('cardapio_restricoes');
    }
};

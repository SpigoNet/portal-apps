<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_receitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedSmallInteger('rendimento')->default(4); // porções
            $table->unsignedSmallInteger('tempo_preparo')->nullable(); // minutos
            $table->string('dificuldade', 10)->default('media'); // facil | media | dificil
            $table->text('modo_preparo')->nullable();
            $table->json('categorias')->nullable();
            $table->json('refeicoes_compativeis')->nullable();
            $table->boolean('favorita')->default(false);
            $table->string('frequencia', 10)->default('media'); // alta | media | baixa
            $table->timestamps();

            $table->unique(['casa_id', 'nome']);
        });

        Schema::create('cardapio_receita_ingredientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receita_id')->constrained('cardapio_receitas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->decimal('quantidade', 12, 3); // unidade base do ingrediente, para o rendimento da receita
            $table->boolean('opcional')->default(false);
            $table->timestamps();
        });

        Schema::create('cardapio_substituicoes_ingrediente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->foreignId('substituto_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->unsignedSmallInteger('compatibilidade')->default(100); // 0-100
            $table->timestamps();

            $table->unique(['ingrediente_id', 'substituto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_substituicoes_ingrediente');
        Schema::dropIfExists('cardapio_receita_ingredientes');
        Schema::dropIfExists('cardapio_receitas');
    }
};

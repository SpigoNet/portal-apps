<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_ingredientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('cardapio_categorias_ingrediente')->nullOnDelete();
            $table->string('nome');
            $table->string('unidade_base', 5)->default('g'); // g | ml | un
            $table->timestamps();

            $table->unique(['casa_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_ingredientes');
    }
};

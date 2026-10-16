<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_itens_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('cardapio_ingredientes')->cascadeOnDelete();
            $table->string('local', 20)->default('despensa'); // despensa | geladeira | freezer | outro
            // Quantidades sempre na unidade base do ingrediente (g, ml ou un).
            $table->decimal('quantidade', 12, 3)->default(0);
            $table->boolean('aproximada')->default(false);
            $table->date('validade')->nullable();
            $table->date('data_entrada')->nullable();
            $table->string('marca')->nullable();
            $table->string('lote')->nullable();
            $table->decimal('quantidade_minima', 12, 3)->nullable();
            $table->decimal('quantidade_ideal', 12, 3)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['casa_id', 'ingrediente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_itens_estoque');
    }
};

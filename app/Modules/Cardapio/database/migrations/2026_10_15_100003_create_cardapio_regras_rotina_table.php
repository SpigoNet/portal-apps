<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_regras_rotina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->constrained('cardapio_pessoas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1 = segunda ... 7 = domingo (ISO)
            $table->string('tipo_refeicao', 30);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['pessoa_id', 'dia_semana', 'tipo_refeicao'], 'cardapio_rotina_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_regras_rotina');
    }
};

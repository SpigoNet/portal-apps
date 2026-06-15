<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_cartelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jogador_id')->constrained('bingo_jogadores')->cascadeOnDelete();
            $table->json('numeros');
            $table->json('marcacoes')->default('[]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_cartelas');
    }
};

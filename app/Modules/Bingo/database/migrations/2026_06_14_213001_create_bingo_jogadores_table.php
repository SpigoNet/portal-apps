<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_jogadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained('bingo_partidas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('token');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('bingo_feito')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_jogadores');
    }
};

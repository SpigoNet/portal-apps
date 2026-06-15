<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_partidas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('tema');
            $table->string('status', 20)->default('espera');
            $table->boolean('modo_gestor')->default(false);
            $table->string('dono_token');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('numeros_sorteados')->default('[]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_partidas');
    }
};

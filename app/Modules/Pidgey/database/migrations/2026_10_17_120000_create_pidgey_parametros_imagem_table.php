<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pidgey_parametros_imagem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provedor_id')->unique()->constrained('ai_provedores')->cascadeOnDelete();
            $table->json('parametros');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pidgey_parametros_imagem');
    }
};

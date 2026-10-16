<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('fase');
            $table->unsignedInteger('tempo_ms');
            $table->timestamps();

            $table->unique(['user_id', 'fase']);
            $table->index(['fase', 'tempo_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_resultados');
    }
};

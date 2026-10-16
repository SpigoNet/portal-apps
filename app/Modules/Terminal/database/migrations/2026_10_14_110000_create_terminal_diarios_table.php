<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('modo', 10)->default('normal');
            $table->date('data');
            $table->unsignedInteger('tempo_ms');
            $table->timestamps();

            $table->unique(['user_id', 'modo', 'data']);
            $table->index(['modo', 'data', 'tempo_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_diarios');
    }
};

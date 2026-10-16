<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_conquistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 40);
            $table->timestamps();

            $table->unique(['user_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_conquistas');
    }
};

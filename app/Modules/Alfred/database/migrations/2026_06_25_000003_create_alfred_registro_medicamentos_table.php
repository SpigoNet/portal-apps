<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_registro_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('medicamento_id')->constrained('alfred_medicamentos')->onDelete('cascade');
            $table->date('data');
            $table->time('hora')->nullable();
            $table->integer('quantidade')->default(1);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'data']);
            $table->index(['medicamento_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_registro_medicamentos');
    }
};

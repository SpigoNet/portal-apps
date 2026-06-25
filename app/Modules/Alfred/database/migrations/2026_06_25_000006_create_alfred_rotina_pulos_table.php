<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_rotina_pulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rotina_id')->constrained('alfred_rotinas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('data_pulo');
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->unique(['rotina_id', 'data_pulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_rotina_pulos');
    }
};

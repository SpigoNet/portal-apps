<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ant_materiais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('ant_materias')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('semestre', 6);
            $table->date('data_aula')->comment('Data da aula para agrupamento');
            $table->string('titulo', 255);
            $table->text('descricao')->nullable();
            $table->text('arquivos')->nullable()->comment('JSON com caminhos dos arquivos no SFTP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ant_materiais');
    }
};

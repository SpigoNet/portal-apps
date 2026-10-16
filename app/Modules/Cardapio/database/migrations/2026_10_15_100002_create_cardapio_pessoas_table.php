<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_pessoas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_id')->constrained('cardapio_casas')->cascadeOnDelete();
            // Login é opcional: criança, empregada etc. existem sem usuário.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nome');
            $table->date('data_nascimento')->nullable();
            $table->string('tipo', 20)->default('adulto');
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_pessoas');
    }
};

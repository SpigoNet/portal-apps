<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Nome da Empresa
            $table->string('documento')->nullable(); // CNPJ ou CPF
            $table->string('email_financeiro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_clientes');
    }
};

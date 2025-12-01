<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_apontamentos', function (Blueprint $table) {
            $table->id();

            // Vincula ao contrato
            $table->foreignId('gh_contrato_id')
                ->constrained('gh_contratos')
                ->onDelete('cascade');

            $table->string('descricao'); // O que foi feito
            $table->date('data_realizacao'); // Quando foi feito

            // Tempo em minutos para precisão (banco salva 90, sistema mostra 1.5h)
            $table->integer('minutos_gastos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_apontamentos');
    }
};

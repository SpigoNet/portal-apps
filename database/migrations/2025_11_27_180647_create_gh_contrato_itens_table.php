<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_contrato_itens', function (Blueprint $table) {
            $table->id();

            // Vincula ao contrato
            $table->foreignId('gh_contrato_id')
                ->constrained('gh_contratos')
                ->onDelete('cascade');

            $table->string('titulo'); // Ex: "Relatório Planejado x Realizado"
            $table->text('descricao')->nullable(); // Detalhes técnicos

            // Horas estipuladas para este item específico
            $table->decimal('horas_estimadas', 8, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_contrato_itens');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_contratos', function (Blueprint $table) {
            $table->id();

            // Vínculo com a Empresa (Cliente)
            $table->foreignId('gh_cliente_id')
                ->constrained('gh_clientes')
                ->onDelete('cascade');

            $table->string('titulo'); // Ex: "Suporte Mensal", "Pacote Desenvolvimento"

            // Tipo: 'fixo' (pacote fechado) ou 'recorrente' (renova mensalmente)
            $table->enum('tipo', ['fixo', 'recorrente'])->default('fixo');

            // Quantidade de horas contratadas
            $table->decimal('horas_contratadas', 8, 2);

            $table->date('data_inicio');
            $table->date('data_fim')->nullable(); // Opcional para recorrente

            // Status: ativo, finalizado, cancelado
            $table->enum('status', ['ativo', 'finalizado', 'cancelado'])->default('ativo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_contratos');
    }
};

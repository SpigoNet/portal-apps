<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_apontamentos', function (Blueprint $table) {
            // Vincula o apontamento a um item específico do escopo (opcional)
            $table->foreignId('gh_contrato_item_id')
                ->nullable()
                ->after('gh_contrato_id')
                ->constrained('gh_contrato_itens')
                ->onDelete('set null'); // Se apagar o item, mantém o histórico de horas, mas desvincula
        });
    }

    public function down(): void
    {
        Schema::table('gh_apontamentos', function (Blueprint $table) {
            $table->dropForeign(['gh_contrato_item_id']);
            $table->dropColumn('gh_contrato_item_id');
        });
    }
};

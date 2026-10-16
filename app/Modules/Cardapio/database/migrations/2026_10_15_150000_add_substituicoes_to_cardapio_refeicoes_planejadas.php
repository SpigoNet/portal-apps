<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cardapio_refeicoes_planejadas', function (Blueprint $table) {
            // Adaptações aplicadas à receita nesta refeição: { ingrediente_id: substituto_id }.
            $table->json('substituicoes')->nullable()->after('justificativa');
        });
    }

    public function down(): void
    {
        Schema::table('cardapio_refeicoes_planejadas', function (Blueprint $table) {
            $table->dropColumn('substituicoes');
        });
    }
};

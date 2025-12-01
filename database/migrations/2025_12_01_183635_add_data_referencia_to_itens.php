<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_contrato_itens', function (Blueprint $table) {
            // Serve para ordenar os meses (Ex: 2025-01-01 para Janeiro)
            $table->date('data_referencia')->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('gh_contrato_itens', function (Blueprint $table) {
            $table->dropColumn('data_referencia');
        });
    }
};

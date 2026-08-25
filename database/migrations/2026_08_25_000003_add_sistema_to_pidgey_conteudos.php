<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pidgey_conteudos_dinamicos', function ($table) {
            $table->boolean('sistema')->default(false);
        });

        // O relatório financeiro é uma fonte de sistema (obtida do Mithril),
        // não um conteúdo cadastrado pelo usuário.
        DB::table('pidgey_conteudos_dinamicos')
            ->where('tipo', 'relatorio_financeiro')
            ->update(['sistema' => true]);
    }

    public function down(): void
    {
        Schema::table('pidgey_conteudos_dinamicos', function ($table) {
            $table->dropColumn('sistema');
        });
    }
};

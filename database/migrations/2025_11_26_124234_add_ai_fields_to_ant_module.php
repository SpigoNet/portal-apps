<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campo para Dicas específicas de cada trabalho
        Schema::table('ant_trabalhos', function (Blueprint $table) {
            $table->text('dicas_correcao')->nullable()->after('descricao')
                ->comment('Instruções para a IA corrigir este trabalho específico');
        });

        // 2. Campo para o Prompt Global (Persona) do Agente
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->text('prompt_agente')->nullable()->after('admins')
                ->comment('Prompt do sistema/persona da IA');
        });
    }

    public function down(): void
    {
        Schema::table('ant_trabalhos', function (Blueprint $table) {
            $table->dropColumn('dicas_correcao');
        });

        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->dropColumn('prompt_agente');
        });
    }
};

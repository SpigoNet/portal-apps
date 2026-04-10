<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_apontamentos', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('gh_contrato_item_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('iniciado_em')
                ->nullable()
                ->after('data_realizacao');

            $table->timestamp('finalizado_em')
                ->nullable()
                ->after('iniciado_em');

            $table->unsignedTinyInteger('apontamento_ativo')
                ->nullable()
                ->after('finalizado_em');

            $table->enum('faturamento_status', ['nao_separado', 'separado', 'aprovado_cliente', 'faturado'])
                ->default('nao_separado')
                ->after('minutos_gastos');

            $table->timestamp('faturamento_selecionado_em')
                ->nullable()
                ->after('faturamento_status');

            $table->foreignId('faturamento_selecionado_por')
                ->nullable()
                ->after('faturamento_selecionado_em')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['gh_contrato_id', 'faturamento_status'], 'gh_apontamentos_contrato_faturamento_idx');
            $table->unique(['user_id', 'apontamento_ativo'], 'gh_apontamentos_unico_ativo_por_usuario');
        });
    }

    public function down(): void
    {
        Schema::table('gh_apontamentos', function (Blueprint $table) {
            $table->dropUnique('gh_apontamentos_unico_ativo_por_usuario');
            $table->dropIndex('gh_apontamentos_contrato_faturamento_idx');

            $table->dropForeign(['faturamento_selecionado_por']);
            $table->dropColumn('faturamento_selecionado_por');

            $table->dropColumn('faturamento_selecionado_em');
            $table->dropColumn('faturamento_status');
            $table->dropColumn('apontamento_ativo');
            $table->dropColumn('finalizado_em');
            $table->dropColumn('iniciado_em');

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pidgey_conteudos_dinamicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->default('texto'); // texto | relatorio_financeiro
            $table->text('conteudo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('agendamento_conteudo_dinamico', function (Blueprint $table) {
            $table->foreignId('agendamento_id')
                ->constrained('pidgey_agendamentos')
                ->onDelete('cascade');
            $table->foreignId('conteudo_dinamico_id')
                ->constrained('pidgey_conteudos_dinamicos')
                ->onDelete('cascade');
            $table->primary(['agendamento_id', 'conteudo_dinamico_id']);
        });

        DB::table('pidgey_conteudos_dinamicos')->insert([
            'nome' => 'Relatório Financeiro (Nami)',
            'tipo' => 'relatorio_financeiro',
            'conteudo' => null,
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_conteudo_dinamico');
        Schema::dropIfExists('pidgey_conteudos_dinamicos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //-- Ordem das Tarefas dentro da Fase
        //ALTER TABLE `portal-apps`.treetask_tarefas ADD COLUMN ordem INT DEFAULT 0;
        //
        //-- Ordem Global (para o Modo Foco)
        //ALTER TABLE `portal-apps`.treetask_tarefas ADD COLUMN ordem_global INT DEFAULT 0;
        // Fazer a adiçao dos campos
        DB::statement("ALTER TABLE `portal-apps`.treetask_tarefas ADD COLUMN ordem INT DEFAULT 0;");
        DB::statement("ALTER TABLE `portal-apps`.treetask_tarefas ADD COLUMN ordem_global INT DEFAULT 0;");


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `portal-apps`.treetask_tarefas DROP COLUMN ordem;");
        DB::statement("ALTER TABLE `portal-apps`.treetask_tarefas DROP COLUMN ordem_global;");
    }
};

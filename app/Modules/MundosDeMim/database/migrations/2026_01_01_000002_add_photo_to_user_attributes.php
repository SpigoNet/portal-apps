<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            // Adiciona o campo para a foto do usuário principal
            $table->string('photo_path')
                ->nullable()
                ->after('user_id')
                ->comment('Caminho da foto de referência para IA');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};

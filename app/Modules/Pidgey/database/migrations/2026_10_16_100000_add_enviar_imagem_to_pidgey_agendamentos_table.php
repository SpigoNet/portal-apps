<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->boolean('enviar_imagem')->default(false)->after('interpretar');
        });
    }

    public function down(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->dropColumn('enviar_imagem');
        });
    }
};

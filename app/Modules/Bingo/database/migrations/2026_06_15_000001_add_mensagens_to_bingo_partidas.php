<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_partidas', function (Blueprint $table) {
            $table->json('mensagens')->nullable()->after('numeros_sorteados');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_partidas', function (Blueprint $table) {
            $table->dropColumn('mensagens');
        });
    }
};

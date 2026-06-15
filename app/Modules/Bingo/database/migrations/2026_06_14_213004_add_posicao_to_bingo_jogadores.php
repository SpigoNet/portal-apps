<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_jogadores', function (Blueprint $table) {
            $table->unsignedTinyInteger('posicao')->nullable()->after('bingo_feito');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_jogadores', function (Blueprint $table) {
            $table->dropColumn('posicao');
        });
    }
};

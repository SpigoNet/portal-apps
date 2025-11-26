<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_alternativas', function (Blueprint $table) {
            $table->text('explicacao')->nullable()->after('correta')->comment('Justificativa do porquê está certa ou errada');
        });
    }

    public function down(): void
    {
        Schema::table('ant_alternativas', function (Blueprint $table) {
            $table->dropColumn('explicacao');
        });
    }
};

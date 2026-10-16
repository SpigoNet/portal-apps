<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->unsignedTinyInteger('estrelas')->default(0)->after('tempo_ms');
        });
    }

    public function down(): void
    {
        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->dropColumn('estrelas');
        });
    }
};

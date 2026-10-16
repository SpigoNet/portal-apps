<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->string('modo', 10)->default('normal')->after('user_id');
        });

        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'fase']);
            $table->dropIndex(['fase', 'tempo_ms']);
        });

        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->unique(['user_id', 'modo', 'fase']);
            $table->index(['modo', 'fase', 'tempo_ms']);
        });
    }

    public function down(): void
    {
        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'modo', 'fase']);
            $table->dropIndex(['modo', 'fase', 'tempo_ms']);
        });

        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->dropColumn('modo');
        });

        Schema::table('terminal_resultados', function (Blueprint $table) {
            $table->unique(['user_id', 'fase']);
            $table->index(['fase', 'tempo_ms']);
        });
    }
};

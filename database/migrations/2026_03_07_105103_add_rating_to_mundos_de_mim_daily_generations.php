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
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};

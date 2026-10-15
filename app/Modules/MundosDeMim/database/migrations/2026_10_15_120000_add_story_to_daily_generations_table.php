<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->text('story')->nullable()->after('final_prompt_used');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->dropColumn('story');
        });
    }
};

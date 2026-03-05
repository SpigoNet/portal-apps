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
        // Fix mundos_de_mim_prompts
        Schema::table('mundos_de_mim_prompts', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->foreign('theme_id')->references('id')->on('mundos_de_mim_themes')->onDelete('cascade');
        });

        // Fix mundos_de_mim_daily_generations
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->dropForeign(['prompt_id']);
            
            $table->foreign('theme_id')->references('id')->on('mundos_de_mim_themes')->onDelete('set null');
            $table->foreign('prompt_id')->references('id')->on('mundos_de_mim_prompts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_daily_generations', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->dropForeign(['prompt_id']);
            
            $table->foreign('theme_id')->references('id')->on('themes')->onDelete('set null');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('set null');
        });

        Schema::table('mundos_de_mim_prompts', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->foreign('theme_id')->references('id')->on('themes')->onDelete('cascade');
        });
    }
};

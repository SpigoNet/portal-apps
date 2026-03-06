<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_theme_examples', function (Blueprint $table) {
            $table->foreignId('prompt_id')
                ->nullable()
                ->after('theme_id')
                ->constrained('mundos_de_mim_prompts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_theme_examples', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prompt_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_modelos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pidgey_agendamentos', function (Blueprint $table) {
            $table->dropForeign(['ai_model_id']);
            $table->dropColumn('ai_model_id');
        });
    }
};

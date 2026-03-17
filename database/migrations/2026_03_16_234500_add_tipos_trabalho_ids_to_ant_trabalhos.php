<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_trabalhos', function (Blueprint $table) {
            // JSON array of ant_tipos_trabalho IDs.
            // When set, replaces the single tipo_trabalho_id for extension validation.
            // tipo_trabalho_id is kept for backward compatibility with existing records.
            $table->json('tipos_trabalho_ids')->nullable()->after('tipo_trabalho_id')
                ->comment('JSON array of ant_tipos_trabalho IDs. Replaces tipo_trabalho_id when present.');
        });
    }

    public function down(): void
    {
        Schema::table('ant_trabalhos', function (Blueprint $table) {
            $table->dropColumn('tipos_trabalho_ids');
        });
    }
};

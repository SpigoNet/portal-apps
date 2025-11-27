<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->string('ia_key', 255)->nullable()->after('ia_url')
                ->comment('Chave de API para Gemini ou OpenAI');
        });
    }

    public function down(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->dropColumn('ia_key');
        });
    }
};

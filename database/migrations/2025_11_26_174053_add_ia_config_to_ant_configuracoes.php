<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->string('ia_driver', 50)->default('pollination')->after('prompt_agente')
                ->comment('pollination | lm_studio');
            $table->string('ia_url', 255)->nullable()->after('ia_driver')
                ->comment('URL para LM Studio ou outra API local');
        });
    }

    public function down(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->dropColumn(['ia_driver', 'ia_url']);
        });
    }
};

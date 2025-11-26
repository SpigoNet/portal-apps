<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            // Armazenará e-mails separados por vírgula ou JSON
            // Ex: "admin@escola.com,diretor@escola.com"
            $table->text('admins')->nullable()->after('semestre_atual');
        });
    }

    public function down(): void
    {
        Schema::table('ant_configuracoes', function (Blueprint $table) {
            $table->dropColumn('admins');
        });
    }
};

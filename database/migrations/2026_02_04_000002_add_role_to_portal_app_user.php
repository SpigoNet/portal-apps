<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        //verificar se a coluna role existe
        if (!Schema::hasColumn('portal_app_user', 'role')) {
            Schema::table('portal_app_user', function (Blueprint $table) {
                $table->enum('role', ['admin', 'user'])
                    ->default('user')
                    ->after('portal_app_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('portal_app_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

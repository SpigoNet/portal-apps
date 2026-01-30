<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('portal_app_user', function (Blueprint $table) {
            $table->enum(
                'role',
                ['admin', 'user']
            )->default('user')->after('portal_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_app_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

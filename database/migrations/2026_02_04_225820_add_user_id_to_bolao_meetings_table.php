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
        if (! Schema::hasTable('bolao_meetings')) {
            return;
        }

        Schema::table('bolao_meetings', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bolao_meetings') || ! Schema::hasColumn('bolao_meetings', 'user_id')) {
            return;
        }

        Schema::table('bolao_meetings', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

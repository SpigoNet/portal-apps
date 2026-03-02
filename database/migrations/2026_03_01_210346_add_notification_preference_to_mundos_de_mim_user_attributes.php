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
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->enum('notification_preference', ['none', 'whatsapp', 'telegram', 'email'])
                ->default('none')
                ->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->dropColumn('notification_preference');
        });
    }
};

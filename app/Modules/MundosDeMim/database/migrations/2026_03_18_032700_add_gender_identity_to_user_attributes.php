<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->string('gender_identity')->nullable()->after('visual_profile');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->dropColumn('gender_identity');
        });
    }
};

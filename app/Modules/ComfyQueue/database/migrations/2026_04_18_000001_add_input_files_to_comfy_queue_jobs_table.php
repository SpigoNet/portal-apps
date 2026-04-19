<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comfy_queue_jobs', function (Blueprint $table) {
            $table->json('input_files')->nullable()->after('required_models');
        });
    }

    public function down(): void
    {
        Schema::table('comfy_queue_jobs', function (Blueprint $table) {
            $table->dropColumn('input_files');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comfy_queue_jobs', function (Blueprint $table) {
            $table->string('prompt_id')->nullable()->after('status');
            $table->json('output_files')->nullable()->after('result_url');
            $table->longText('execution_log')->nullable()->after('output_files');
            $table->timestamp('started_at')->nullable()->after('retry_count');
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('comfy_queue_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'prompt_id',
                'output_files',
                'execution_log',
                'started_at',
                'finished_at',
            ]);
        });
    }
};
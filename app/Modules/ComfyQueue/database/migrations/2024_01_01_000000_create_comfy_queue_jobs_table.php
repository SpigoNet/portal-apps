<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comfy_queue_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('type')->nullable();
            $table->jsonb('params')->nullable();
            $table->string('status')->default('pending');
            $table->text('result_url')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->text('error')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comfy_queue_jobs');
    }
};

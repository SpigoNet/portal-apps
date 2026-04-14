<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comfy_queue_job_models', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->json('json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comfy_queue_job_models');
    }
};
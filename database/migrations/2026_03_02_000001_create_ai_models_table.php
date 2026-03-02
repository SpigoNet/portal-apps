<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('name');
            $table->string('driver');
            $table->string('model');
            $table->text('description')->nullable();
            $table->boolean('supports_image_input')->default(false);
            $table->boolean('supports_video_output')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('pricing')->nullable();
            $table->boolean('paid_only')->default(false);
            $table->timestamps();
            $table->unique(['model', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};

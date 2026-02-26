<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mundos_de_mim_ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver');
            $table->string('model')->unique();
            $table->text('description')->nullable();
            $table->boolean('supports_image_input')->default(false);
            $table->boolean('supports_video_output')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('pricing')->nullable();
            $table->boolean('paid_only')->default(false);
            $table->timestamps();
        });

        Schema::create('mundos_de_mim_user_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_provider_id')->constrained('mundos_de_mim_ai_providers')->onDelete('cascade');
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('mundos_de_mim_default_ai_provider_id')
                ->nullable()
                ->constrained('mundos_de_mim_ai_providers')
                ->onDelete('set null')
                ->after('credits');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['mundos_de_mim_default_ai_provider_id']);
            $table->dropColumn('mundos_de_mim_default_ai_provider_id');
        });
        Schema::dropIfExists('mundos_de_mim_user_ai_settings');
        Schema::dropIfExists('mundos_de_mim_ai_providers');
    }
};

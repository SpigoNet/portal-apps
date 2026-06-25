<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('treetask_url')->nullable()->default('https://apps.spigo.net');
            $table->string('treetask_user_id')->nullable();
            $table->string('treetask_token')->nullable();
            $table->integer('meta_agua_ml')->default(2500);
            $table->enum('energia_atual', ['baixa', 'media', 'alta'])->default('media');
            $table->boolean('modo_dia_ruim')->default(false);
            $table->timestamp('dia_ruim_ativado_em')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_user_profiles');
    }
};

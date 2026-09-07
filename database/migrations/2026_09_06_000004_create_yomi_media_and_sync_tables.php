<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mídias espelhadas localmente (capas, personagens, criadores, etc.)
        Schema::create('yomi_midias', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id');
            $table->string('type', 30);
            $table->string('source_url', 500);
            $table->string('storage_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('status', 30)->default('pendente');
            $table->timestamp('downloaded_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('checksum');
        });

        // Histórico de sincronizações
        Schema::create('yomi_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->nullable()->constrained('yomi_mangas')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('operation', 30);
            $table->string('status', 30);
            $table->string('error_type', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('http_status')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('manga_id');
            $table->index('created_at');
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yomi_sync_logs');
        Schema::dropIfExists('yomi_midias');
    }
};

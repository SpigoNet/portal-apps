<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Progresso resumido por usuário e obra
        Schema::create('yomi_progresso_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->string('status', 30)->default('pretendo_ler');
            $table->decimal('ultimo_capitulo_lido', 8, 2)->nullable();
            $table->unsignedInteger('ultimo_volume_lido')->nullable();
            $table->unsignedTinyInteger('nota')->nullable();
            $table->boolean('favorito')->default(false);
            $table->text('observacoes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'manga_id']);
            $table->index('user_id');
            $table->index('status');
        });

        // Progresso granular: capítulos individualmente concluídos
        Schema::create('yomi_usuario_capitulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->foreignId('capitulo_id')->constrained('yomi_capitulos')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'capitulo_id']);
            $table->index(['user_id', 'manga_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yomi_usuario_capitulos');
        Schema::dropIfExists('yomi_progresso_usuario');
    }
};

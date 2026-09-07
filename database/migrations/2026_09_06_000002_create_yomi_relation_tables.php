<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identificadores externos (provedor -> id) sem acoplar o catálogo a MAL/AniList
        Schema::create('yomi_manga_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('external_id', 100);
            $table->timestamps();

            $table->unique(['manga_id', 'provider']);
            $table->index(['provider', 'external_id']);
        });

        // Títulos alternativos / sinônimos
        Schema::create('yomi_manga_titulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->string('titulo', 255);
            $table->string('tipo', 30)->default('alternativo');
            $table->string('idioma', 10)->nullable();
            $table->timestamps();

            $table->index('manga_id');
        });

        // Relação obra <-> gênero
        Schema::create('yomi_manga_generos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->foreignId('genero_id')->constrained('yomi_generos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['manga_id', 'genero_id']);
        });

        // Relação obra <-> criador (com papel/função)
        Schema::create('yomi_manga_criadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->foreignId('criador_id')->constrained('yomi_criadores')->cascadeOnDelete();
            $table->string('papel', 50)->nullable();
            $table->timestamps();

            $table->unique(['manga_id', 'criador_id', 'papel']);
        });

        // Relação obra <-> personagem (com papel)
        Schema::create('yomi_manga_personagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->foreignId('personagem_id')->constrained('yomi_personagens')->cascadeOnDelete();
            $table->string('papel', 50)->nullable();
            $table->timestamps();

            $table->unique(['manga_id', 'personagem_id', 'papel']);
        });

        // Capítulos conhecidos
        Schema::create('yomi_capitulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained('yomi_mangas')->cascadeOnDelete();
            $table->decimal('numero', 8, 2)->nullable();
            $table->string('titulo', 255)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->string('provider', 30)->nullable();
            $table->date('data_publicacao')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('manga_id');
            $table->unique(['manga_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yomi_capitulos');
        Schema::dropIfExists('yomi_manga_personagens');
        Schema::dropIfExists('yomi_manga_criadores');
        Schema::dropIfExists('yomi_manga_generos');
        Schema::dropIfExists('yomi_manga_titulos');
        Schema::dropIfExists('yomi_manga_external_ids');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo normalizado de gêneros
        Schema::create('yomi_generos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->timestamps();
        });

        // Criadores (autores, ilustradores, roteiristas, etc.)
        Schema::create('yomi_criadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->unique();
            $table->string('nome_original', 255)->nullable();
            $table->string('url_externa', 500)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->timestamps();
        });

        // Personagens
        Schema::create('yomi_personagens', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->unique();
            $table->string('nome_original', 255)->nullable();
            $table->text('descricao')->nullable();
            $table->string('url_externa', 500)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->timestamps();
        });

        // Obras de mangá (tabela central do catálogo)
        Schema::create('yomi_mangas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('titulo_original', 255)->nullable();
            $table->text('sinopse')->nullable();
            $table->string('status_publicacao', 30)->default('desconhecido');
            $table->string('tipo', 30)->nullable();
            $table->unsignedInteger('capitulos_conhecidos')->nullable();
            $table->unsignedInteger('volumes_conhecidos')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->decimal('nota_media', 3, 2)->nullable();
            $table->string('classificacao_etaria', 30)->nullable();
            $table->string('fonte_original', 30)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();
            $table->string('sync_status', 30)->default('pendente');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();

            $table->index('titulo');
            $table->index('sync_status');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yomi_mangas');
        Schema::dropIfExists('yomi_personagens');
        Schema::dropIfExists('yomi_criadores');
        Schema::dropIfExists('yomi_generos');
    }
};

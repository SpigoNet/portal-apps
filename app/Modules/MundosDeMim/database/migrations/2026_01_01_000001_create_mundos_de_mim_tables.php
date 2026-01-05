<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela: user_attributes [cite: 24]
        Schema::create('mundos_de_mim_user_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Garante limpeza se user for deletado

            // Dados Biométricos [cite: 26-29]
            $table->float('height')->nullable()->comment('Altura em cm');
            $table->float('weight')->nullable()->comment('Peso em kg');
            $table->string('body_type')->default('normal')->comment('Ex: atletico, slim');
            $table->string('eye_color');
            $table->string('hair_type');

            $table->timestamps();
        });

        // Tabela: related_people [cite: 30]
        Schema::create('mundos_de_mim_related_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Dados da Pessoa [cite: 32-34]
            $table->string('name');
            $table->string('relationship')->comment('Ex: namorado, filho, avó');
            $table->string('photo_path')->comment('Caminho seguro no storage');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_people');
        Schema::dropIfExists('user_attributes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_rotina_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cor', 7)->default('#95a5a6');
            $table->string('icone', 50)->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativa')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        $categorias = [
            ['nome' => 'Saúde', 'slug' => 'saude', 'cor' => '#e74c3c', 'icone' => '❤️', 'ordem' => 1],
            ['nome' => 'Lazer', 'slug' => 'lazer', 'cor' => '#9b59b6', 'icone' => '🎮', 'ordem' => 2],
            ['nome' => 'Financeiro', 'slug' => 'financeiro', 'cor' => '#27ae60', 'icone' => '💰', 'ordem' => 3],
            ['nome' => 'Família', 'slug' => 'familia', 'cor' => '#f39c12', 'icone' => '👨‍👩‍👧‍👦', 'ordem' => 4],
            ['nome' => 'Estudo', 'slug' => 'estudo', 'cor' => '#3498db', 'icone' => '📚', 'ordem' => 5],
            ['nome' => 'Trabalho', 'slug' => 'trabalho', 'cor' => '#34495e', 'icone' => '💼', 'ordem' => 6],
            ['nome' => 'Outro', 'slug' => 'outro', 'cor' => '#95a5a6', 'icone' => '📌', 'ordem' => 99],
        ];

        DB::table('alfred_rotina_categorias')->insert($categorias);
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_rotina_categorias');
    }
};

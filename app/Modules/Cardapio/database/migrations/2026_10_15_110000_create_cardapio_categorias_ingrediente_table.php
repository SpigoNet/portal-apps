<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapio_categorias_ingrediente', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        $agora = now();
        $categorias = ['Hortifruti', 'Carnes', 'Laticínios', 'Mercearia', 'Padaria', 'Bebidas', 'Congelados', 'Temperos', 'Limpeza e higiene', 'Outros'];

        DB::table('cardapio_categorias_ingrediente')->insert(
            array_map(fn (string $nome, int $i): array => ['nome' => $nome, 'ordem' => $i, 'created_at' => $agora, 'updated_at' => $agora], $categorias, array_keys($categorias))
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapio_categorias_ingrediente');
    }
};

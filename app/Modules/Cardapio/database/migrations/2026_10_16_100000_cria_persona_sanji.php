<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persona do bot do Cardápio. O token do bot (@BotFather) é preenchido depois, na persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alfred_personas') || DB::table('alfred_personas')->where('slug', 'sanji')->exists()) {
            return;
        }

        $dados = [
            'name' => 'Sanji',
            'slug' => 'sanji',
            'canal' => 'telegram',
            'personality' => json_encode([
                'description' => 'Sanji, o cozinheiro do bando (One Piece): cavalheiro, apaixonado por cozinhar, exigente com desperdício de comida e com a qualidade dos ingredientes. Fala de forma elegante e galante, com leve dramatismo, e leva a cozinha muito a sério.',
                'tone' => 'galante, caprichoso, direto',
                'greetings' => ['Pode deixar a cozinha comigo!'],
            ], JSON_UNESCAPED_UNICODE),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Colunas que podem não existir em todos os ambientes.
        DB::table('alfred_personas')->insert(array_intersect_key($dados, array_flip(Schema::getColumnListing('alfred_personas'))));
    }

    public function down(): void
    {
        if (Schema::hasTable('alfred_personas')) {
            DB::table('alfred_personas')->where('slug', 'sanji')->whereNull('telegram_token')->delete();
        }
    }
};

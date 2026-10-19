<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_personas', function (Blueprint $table) {
            $table->text('aparencia')->nullable()->after('personality');
            $table->string('imagem_referencia')->nullable()->after('aparencia');
        });
    }

    public function down(): void
    {
        Schema::table('alfred_personas', function (Blueprint $table) {
            $table->dropColumn(['aparencia', 'imagem_referencia']);
        });
    }
};

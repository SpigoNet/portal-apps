<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('treetask_lore_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('universo', 50)->comment('Nome do universo Geek/Pop');
            $table->text('prompt_personagem')->comment('Instrução de roleplay para a IA');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('treetask_lore_prompts');
    }
};

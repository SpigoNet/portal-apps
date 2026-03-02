<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('ai_provedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('url_json_modelos')->nullable();
            $table->json('default_input_types')->nullable();
            $table->json('default_output_types')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provedor_id')->constrained('ai_provedores')->onDelete('cascade');
            $table->string('modelo_id_externo');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->json('input_types');
            $table->json('output_types');
            $table->json('pricing')->nullable();
            $table->json('raw_data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_modelos');
        Schema::dropIfExists('ai_provedores');
    }
};

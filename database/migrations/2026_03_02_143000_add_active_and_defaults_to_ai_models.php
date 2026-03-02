<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ai_modelos', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('raw_data');
        });

        Schema::create('ai_modelos_padrao', function (Blueprint $table) {
            $table->id();
            $table->string('input_type');
            $table->string('output_type');
            $table->foreignId('ai_modelo_id')->constrained('ai_modelos')->onDelete('cascade');
            $table->unique(['input_type', 'output_type']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_modelos_padrao');
        Schema::table('ai_modelos', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

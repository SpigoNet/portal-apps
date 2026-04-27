<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('gh_contrato_itens', function (Blueprint $table) {
            $table->boolean('homologado')->default(false)->after('data_referencia');
            $table->unsignedBigInteger('homologado_por')->nullable()->after('homologado');
            $table->timestamp('homologado_em')->nullable()->after('homologado_por');
            $table->string('homologado_hash', 128)->nullable()->after('homologado_em');
        });
    }

    public function down()
    {
        Schema::table('gh_contrato_itens', function (Blueprint $table) {
            $table->dropColumn(['homologado', 'homologado_por', 'homologado_em', 'homologado_hash']);
        });
    }
};

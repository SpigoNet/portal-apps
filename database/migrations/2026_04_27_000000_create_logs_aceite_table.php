<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('logs_aceite', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gh_contrato_id')->nullable()->index();
            $table->unsignedBigInteger('gh_contrato_item_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('snapshot_hash', 128)->nullable();
            $table->longText('snapshot_json')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs_aceite');
    }
};

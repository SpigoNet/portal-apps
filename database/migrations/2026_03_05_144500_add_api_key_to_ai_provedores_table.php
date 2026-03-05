<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ai_provedores', function (Blueprint $table) {
            $table->text('api_key')->nullable()->after('url_json_modelos');
        });
    }

    public function down()
    {
        Schema::table('ai_provedores', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
};

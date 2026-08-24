<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_personas', function (Blueprint $table) {
            $table->string('canal')->default('whatsapp')->after('whatsapp_group_jid');
            $table->string('telegram_token')->nullable()->after('canal');
            $table->string('telegram_chat_id')->nullable()->after('telegram_token');
        });
    }

    public function down(): void
    {
        Schema::table('alfred_personas', function (Blueprint $table) {
            $table->dropColumn(['canal', 'telegram_token', 'telegram_chat_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mundos_de_mim_ai_providers', function (Blueprint $table) {
            $table->json('pricing')->nullable()->after('sort_order');
            $table->boolean('paid_only')->default(false)->after('pricing');
        });
    }

    public function down(): void
    {
        Schema::table('mundos_de_mim_ai_providers', function (Blueprint $table) {
            $table->dropColumn(['pricing', 'paid_only']);
        });
    }
};

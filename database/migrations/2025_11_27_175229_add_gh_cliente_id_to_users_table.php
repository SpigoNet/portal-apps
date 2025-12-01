<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Um usuário pode pertencer a um cliente (nullable para admins do sistema)
            $table->foreignId('gh_cliente_id')
                ->nullable()
                ->after('id')
                ->constrained('gh_clientes')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gh_cliente_id']);
            $table->dropColumn('gh_cliente_id');
        });
    }
};

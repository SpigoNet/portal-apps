<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin: Gerencia tudo
            // dev: Cria contratos e aponta horas
            // client: Apenas visualiza os dados da própria empresa
            $table->enum('gh_role', ['admin', 'dev', 'client'])
                ->default('client')
                ->after('gh_cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gh_role');
        });
    }
};

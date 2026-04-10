<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE gh_contratos MODIFY COLUMN tipo ENUM('fixo', 'recorrente', 'livre') NOT NULL DEFAULT 'fixo'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('gh_contratos')
            ->where('tipo', 'livre')
            ->update(['tipo' => 'fixo']);

        DB::statement("ALTER TABLE gh_contratos MODIFY COLUMN tipo ENUM('fixo', 'recorrente') NOT NULL DEFAULT 'fixo'");
    }
};

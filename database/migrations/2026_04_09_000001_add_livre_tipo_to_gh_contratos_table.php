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

        if (DB::getDriverName() === 'pgsql') {
            $this->trocarCheckPgsql("'fixo', 'recorrente', 'livre'");

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

        if (DB::getDriverName() === 'pgsql') {
            $this->trocarCheckPgsql("'fixo', 'recorrente'");

            return;
        }

        DB::statement("ALTER TABLE gh_contratos MODIFY COLUMN tipo ENUM('fixo', 'recorrente') NOT NULL DEFAULT 'fixo'");
    }

    /** No PostgreSQL o enum do Laravel é um varchar com CHECK; troca a restrição pelos valores novos. */
    private function trocarCheckPgsql(string $valores): void
    {
        DB::statement('ALTER TABLE gh_contratos DROP CONSTRAINT IF EXISTS gh_contratos_tipo_check');
        DB::statement("ALTER TABLE gh_contratos ADD CONSTRAINT gh_contratos_tipo_check CHECK (tipo IN ($valores))");
    }
};

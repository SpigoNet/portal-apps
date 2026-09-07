<?php

namespace Tests\Unit\Yomi\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra apenas a base necessária para o domínio Yomi (users + yomi_*).
 *
 * As migrations legadas dos demais módulos contêm trechos específicos do MySQL
 * e problemas de ordenação que impedem `migrate:fresh` em ambientes limpos por
 * sqlite. Este trait isola o escopo do Yomi para manter os testes rápidos e
 * determinísticos, sem depender da cadeia completa de migrations do portal.
 */
trait RefreshYomiDatabase
{
    protected function refreshYomiDatabase(): void
    {
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->whereIn('migration', array_map(
                    static fn (string $path): string => basename($path, '.php'),
                    $this->yomiMigrationPaths(),
                ))
                ->delete();
        }

        Schema::disableForeignKeyConstraints();

        foreach ($this->yomiTables() as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        foreach ($this->yomiMigrationPaths() as $path) {
            $this->artisan('migrate', ['--path' => $path]);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function yomiMigrationPaths(): array
    {
        return [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_09_06_000001_create_yomi_catalog_tables.php',
            'database/migrations/2026_09_06_000002_create_yomi_relation_tables.php',
            'database/migrations/2026_09_06_000003_create_yomi_user_progress_tables.php',
            'database/migrations/2026_09_06_000004_create_yomi_media_and_sync_tables.php',
            'database/migrations/2026_09_06_000005_create_yomi_settings_table.php',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function yomiTables(): array
    {
        return [
            'yomi_settings',
            'yomi_usuario_capitulos',
            'yomi_progresso_usuario',
            'yomi_midias',
            'yomi_sync_logs',
            'yomi_capitulos',
            'yomi_manga_personagens',
            'yomi_manga_criadores',
            'yomi_manga_generos',
            'yomi_manga_titulos',
            'yomi_manga_external_ids',
            'yomi_mangas',
            'yomi_personagens',
            'yomi_criadores',
            'yomi_generos',
            'users',
            'password_reset_tokens',
            'sessions',
        ];
    }
}

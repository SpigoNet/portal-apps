<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TEXTO_PADRAO = 'Apontamento iniciado via tela mobile.';

    public function up(): void
    {
        if (! Schema::hasTable('gh_apontamentos')) {
            return;
        }

        DB::table('gh_apontamentos')
            ->where('descricao', 'like', '%'.self::TEXTO_PADRAO.'%')
            ->orderBy('id')
            ->each(function (object $apontamento): void {
                $nova = trim(str_replace(self::TEXTO_PADRAO, '', (string) $apontamento->descricao));
                $nova = trim(preg_replace("/\n{2,}/", "\n", $nova));

                DB::table('gh_apontamentos')
                    ->where('id', $apontamento->id)
                    ->update(['descricao' => $nova]);
            });
    }

    public function down(): void
    {
        // Limpeza de dados: o texto removido não é restaurado.
    }
};

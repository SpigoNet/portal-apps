<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ICONES = [
        '/admin/apps' => 'images/apps/admin.png',
        '/alfred' => 'images/apps/alfred.png',
        '/ant' => 'images/apps/ant.png',
        '/bingo' => 'images/apps/bingo.png',
        '/bolao' => 'images/apps/bolao.png',
        '/dspace-forms-editor' => 'images/apps/dspace.png',
        '/gestor-horas' => 'images/apps/gestor-horas.png',
        '/metricas-sistema' => 'images/apps/metricas.png',
        '/mithril' => 'images/apps/mithril.png',
        '/mundos-de-mim' => 'images/apps/mundos.png',
        '/pidgey' => 'images/apps/pidgey.png',
        '/streaming-manager' => 'images/apps/streaming.png',
        '/treetask' => 'images/apps/treetask.png',
        '/yomi' => 'images/apps/yomi.png',
    ];

    public function up(): void
    {
        foreach (self::ICONES as $startLink => $icone) {
            DB::table('portal_apps')->where('start_link', $startLink)->update(['icon' => $icone]);
        }
    }

    public function down(): void
    {
        // Ícones anteriores não são restauráveis.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TEMA_PADRAO = '#ccf381';

    private const FUNDO_PADRAO = '#1a1b26';

    /** start_link => cor do ícone (usada no tema e na splash screen). */
    private const CORES = [
        '/admin' => '#334155',
        '/alfred' => '#1f2937',
        '/ant' => '#d97706',
        '/bingo' => '#dc2626',
        '/bolao' => '#4f46e5',
        '/comfy-queue' => '#0ea5e9',
        '/dspace-forms-editor' => '#0ea5e9',
        '/ferramentas/whatsapp' => '#25d366',
        '/gestor-horas' => '#2563eb',
        '/metricas-sistema' => '#dc2626',
        '/mithril' => '#9333ea',
        '/mundos-de-mim' => '#db2777',
        '/pidgey' => '#0d9488',
        '/quebra-cabeca' => '#2e1065',
        '/streaming-manager' => '#ccf381',
        '/treetask' => '#16a34a',
        '/vocabulario-controlado' => '#d97706',
        '/yomi' => '#121014',
    ];

    /** start_link => nome curto (embaixo do ícone na tela inicial). */
    private const NOMES_CURTOS = [
        '/admin' => 'Portal',
        '/bolao' => 'Bolão',
        '/comfy-queue' => 'ComfyUI',
        '/dspace-forms-editor' => 'DSpace',
        '/ferramentas/whatsapp' => 'WhatsApp',
        '/gestor-horas' => 'Clientes',
        '/metricas-sistema' => 'Métricas',
        '/mithril' => 'Mithril',
        '/vocabulario-controlado' => 'Vocabulário',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('portal_apps') || ! Schema::hasColumn('portal_apps', 'pwa_display')) {
            return;
        }

        // Todos os apps abrem em tela cheia ao serem instalados.
        DB::table('portal_apps')->update(['pwa_display' => 'fullscreen']);

        foreach (self::CORES as $startLink => $cor) {
            DB::table('portal_apps')->where('start_link', $startLink)->where('pwa_theme_color', self::TEMA_PADRAO)->update(['pwa_theme_color' => $cor]);
            DB::table('portal_apps')->where('start_link', $startLink)->where('pwa_background_color', self::FUNDO_PADRAO)->update(['pwa_background_color' => $cor]);
        }

        foreach (self::NOMES_CURTOS as $startLink => $nome) {
            DB::table('portal_apps')->where('start_link', $startLink)->whereNull('pwa_short_name')->update(['pwa_short_name' => $nome]);
        }
    }

    public function down(): void
    {
        // Configuração de dados: não reversível de forma segura.
    }
};

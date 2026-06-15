<?php

use App\Models\PortalApp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        $data = [
            'title' => 'Bingo',
            'description' => 'Jogo de bingo com temas divertidos para toda família! Reúna amigos e família, escolha um tema e divirta-se marcando as cartelas.',
            'icon' => null,
            'visibility' => 'public',
        ];

        if (Schema::hasColumn('portal_apps', 'pwa_short_name')) {
            $data['pwa_short_name'] = 'Bingo';
            $data['pwa_background_color'] = '#fbbf24';
            $data['pwa_theme_color'] = '#f59e0b';
            $data['pwa_display'] = 'standalone';
            $data['pwa_orientation'] = 'portrait';
            $data['pwa_scope'] = '/bingo';
        }

        PortalApp::firstOrCreate(
            ['start_link' => '/bingo'],
            $data
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        PortalApp::where('start_link', '/bingo')->delete();
    }
};

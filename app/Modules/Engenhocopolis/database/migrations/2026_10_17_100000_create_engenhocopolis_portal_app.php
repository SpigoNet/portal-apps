<?php

use App\Models\Package;
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

        $grupo = Schema::hasTable('packages')
            ? Package::firstOrCreate(['name' => 'Jogos'], ['description' => 'Jogos e passatempos do portal.'])
            : null;

        PortalApp::firstOrCreate(
            ['start_link' => '/engenhocopolis'],
            [
                'title' => 'Engenhocópolis',
                'description' => 'Quebra-cabeça isométrico de automação: monte esteiras, separadores e desviadores para levar cada pacote ao coletor certo.',
                'icon' => 'images/apps/engenhocopolis.png',
                'visibility' => 'private',
                'package_id' => $grupo?->id,
                'pwa_short_name' => 'Engenhocópolis',
                'pwa_display' => 'fullscreen',
                'pwa_orientation' => 'any',
                'pwa_theme_color' => '#1b232d',
                'pwa_background_color' => '#1b232d',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        PortalApp::where('start_link', '/engenhocopolis')->delete();
    }
};

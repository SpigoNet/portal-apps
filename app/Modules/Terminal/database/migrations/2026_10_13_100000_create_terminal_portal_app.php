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
            ['start_link' => '/terminal'],
            [
                'title' => 'Terminal',
                'description' => 'Puzzle de ônibus dentro de um computador antigo: encha os ônibus na ordem certa antes que o terminal fique sem vaga.',
                'icon' => 'images/apps/terminal.png',
                'visibility' => 'private',
                'package_id' => $grupo?->id,
                'pwa_short_name' => 'Terminal',
                'pwa_display' => 'fullscreen',
                'pwa_orientation' => 'portrait',
                'pwa_theme_color' => '#03120a',
                'pwa_background_color' => '#03120a',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        PortalApp::where('start_link', '/terminal')->delete();
    }
};

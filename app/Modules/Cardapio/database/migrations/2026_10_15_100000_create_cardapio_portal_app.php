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
            ? Package::firstOrCreate(['name' => 'Pessoal'], ['description' => 'Apps de uso pessoal e da casa.'])
            : null;

        PortalApp::firstOrCreate(
            ['start_link' => '/cardapio'],
            [
                'title' => 'Cardápio',
                'description' => 'Assistente de planejamento alimentar da casa: estoque, receitas, cardápio semanal e lista de compras.',
                'icon' => 'images/apps/cardapio.png',
                'visibility' => 'private',
                'package_id' => $grupo?->id,
                'pwa_short_name' => 'Cardápio',
                'pwa_display' => 'standalone',
                'pwa_orientation' => 'portrait',
                'pwa_theme_color' => '#166534',
                'pwa_background_color' => '#f0fdf4',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        PortalApp::where('start_link', '/cardapio')->delete();
    }
};

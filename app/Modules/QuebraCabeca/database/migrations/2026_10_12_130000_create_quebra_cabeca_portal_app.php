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

        PortalApp::firstOrCreate(
            ['start_link' => '/quebra-cabeca'],
            [
                'title' => 'Quebra-Cabeça',
                'description' => 'Quebra-cabeça clássico: reordene as peças retangulares da imagem até montá-la.',
                'icon' => 'images/apps/quebra-cabeca.png',
                'visibility' => 'private',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_apps')) {
            return;
        }

        PortalApp::where('start_link', '/quebra-cabeca')->delete();
    }
};

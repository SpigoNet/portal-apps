<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_apps', function (Blueprint $table) {
            // Nome curto (obrigatório para PWA ficar bonito na home do celular)
            $table->string('pwa_short_name', 30)->nullable()->after('title')
                ->comment('Nome que aparece embaixo do ícone no celular');

            // Cores e Comportamento
            $table->string('pwa_background_color', 9)->default('#1a1b26')->after('icon')
                ->comment('Cor de fundo da Splash Screen (Padrão Dark Spigo)');

            $table->string('pwa_theme_color', 9)->default('#ccf381')->after('pwa_background_color')
                ->comment('Cor da barra de status (Padrão Lime Spigo)');

            $table->enum('pwa_display', ['standalone', 'fullscreen', 'minimal-ui', 'browser'])
                ->default('standalone')
                ->after('pwa_theme_color');

            $table->enum('pwa_orientation', ['any', 'natural', 'portrait', 'landscape'])
                ->default('any')
                ->after('pwa_display');

            // Escopo (importante para o app não abrir link externo sem querer)
            $table->string('pwa_scope', 255)->nullable()->after('start_link')
                ->comment('Define a URL base do escopo do PWA');
        });
    }

    public function down(): void
    {
        Schema::table('portal_apps', function (Blueprint $table) {
            $table->dropColumn([
                'pwa_short_name',
                'pwa_background_color',
                'pwa_theme_color',
                'pwa_display',
                'pwa_orientation',
                'pwa_scope'
            ]);
        });
    }
};

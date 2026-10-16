<?php

namespace Tests\Feature;

use App\Models\PortalApp;
use App\Models\PortalAppUser;
use App\Models\User;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A cadeia completa de migrations não roda em sqlite; migra só o necessário.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2025_10_05_035231_create_packages_table.php',
            '2025_10_05_035312_create_portal_apps_table.php',
            '2025_10_05_035349_create_portal_app_user_table.php',
            '2026_02_04_000002_add_role_to_portal_app_user.php',
            '2026_01_28_144103_wpa.php',
            '2025_12_02_193624_create_metricas_acessos_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/QuebraCabeca/database/migrations/2026_10_12_130001_create_quebra_cabeca_imagens_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/QuebraCabeca/database/migrations/2026_10_12_140000_create_quebra_cabeca_grupos_table.php']);
    }

    private function app(array $dados = []): PortalApp
    {
        return PortalApp::create($dados + [
            'title' => 'Quebra-Cabeça',
            'description' => 'Jogo de peças.',
            'icon' => 'images/apps/quebra-cabeca.png',
            'start_link' => '/quebra-cabeca',
            'visibility' => 'private',
            'pwa_display' => 'fullscreen',
        ]);
    }

    public function test_manifest_usa_o_icone_do_app_e_tela_cheia(): void
    {
        $app = $this->app();

        $resposta = $this->get("/manifest/{$app->id}/manifest.json")->assertOk();
        $manifest = $resposta->json();

        $this->assertSame('/quebra-cabeca', $manifest['id']);
        $this->assertSame('/quebra-cabeca', $manifest['start_url']);
        $this->assertSame('/quebra-cabeca', $manifest['scope']);
        $this->assertSame('fullscreen', $manifest['display']);
        $this->assertSame(['fullscreen', 'standalone'], $manifest['display_override']);

        $icones = collect($manifest['icons']);
        $this->assertStringEndsWith('/images/apps/quebra-cabeca.png', $icones->firstWhere('sizes', '192x192')['src']);
        $this->assertStringEndsWith('/images/apps/maskable/quebra-cabeca.png', $icones->firstWhere('purpose', 'maskable')['src']);
        $this->assertSame('any', $icones->firstWhere('sizes', '512x512')['purpose']);
    }

    public function test_manifest_com_icone_antigo_cai_no_icone_do_portal_e_respeita_o_escopo(): void
    {
        $app = $this->app(['icon' => 'fa-solid fa-star', 'start_link' => '/bingo', 'pwa_scope' => '/bingo/']);

        $manifest = $this->get("/manifest/{$app->id}/manifest.json")->json();

        $this->assertSame('/bingo/', $manifest['scope']);
        $this->assertStringEndsWith('/apple-touch-icon.png', $manifest['icons'][0]['src']);
        $this->assertCount(2, $manifest['icons']);
    }

    public function test_pagina_do_modulo_aponta_para_o_manifest_e_o_icone_do_app(): void
    {
        $app = $this->app();

        $this->actingAs(User::factory()->create())
            ->get('/quebra-cabeca')
            ->assertOk()
            ->assertSee("/manifest/{$app->id}/manifest.json", false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('/images/apps/quebra-cabeca.png', false)
            ->assertSee('apple-mobile-web-app-capable', false);
    }

    public function test_admin_salva_configuracao_pwa_do_app(): void
    {
        $app = $this->app();
        $admin = User::factory()->create();
        $appAdmin = $this->app(['title' => 'Admin', 'start_link' => '/admin']);
        PortalAppUser::create(['portal_app_id' => $appAdmin->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $this->actingAs($admin)->put("/admin/apps/{$app->id}", [
            'title' => $app->title,
            'description' => $app->description,
            'start_link' => $app->start_link,
            'visibility' => 'private',
            'pwa_display' => 'standalone',
            'pwa_theme_color' => '#112233',
        ]);

        $app->refresh();
        $this->assertSame('standalone', $app->pwa_display);
        $this->assertSame('#112233', $app->pwa_theme_color);
    }
}

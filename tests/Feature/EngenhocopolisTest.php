<?php

namespace Tests\Feature;

use App\Models\PortalApp;
use App\Models\User;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class EngenhocopolisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A cadeia completa de migrations não roda em sqlite; migra só o necessário.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2025_10_05_035231_create_packages_table.php',
            '2025_10_05_035312_create_portal_apps_table.php',
            '2026_01_28_144103_wpa.php',
            '2025_12_02_193624_create_metricas_acessos_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/Engenhocopolis/database/migrations/2026_10_17_100000_create_engenhocopolis_portal_app.php']);
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/engenhocopolis')->assertRedirect('/login');
    }

    public function test_usuario_logado_ve_o_jogo_com_pwa(): void
    {
        $app = PortalApp::where('start_link', '/engenhocopolis')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get('/engenhocopolis')
            ->assertOk()
            ->assertSee('engenhocopolis()', false)
            ->assertSee('ENGENHO-LOGICA-INICIO', false)
            ->assertSee('EXECUTAR')
            ->assertSee("/manifest/{$app->id}/manifest.json", false)
            ->assertSee('/images/apps/engenhocopolis.png', false);
    }

    public function test_migration_cria_o_app_privado_no_grupo_jogos(): void
    {
        $app = PortalApp::where('start_link', '/engenhocopolis')->firstOrFail();

        $this->assertSame('Engenhocópolis', $app->title);
        $this->assertSame('private', $app->visibility);
        $this->assertSame('Jogos', $app->package->name);
        $this->assertFileExists(public_path($app->icon));
        $this->assertNotNull($app->maskable_icon_file);
    }

    public function test_todas_as_fases_tem_solucao_verificada(): void
    {
        $node = (new ExecutableFinder)->find('node');
        if (! $node) {
            $this->markTestSkipped('Node não está instalado.');
        }

        $processo = new Process([$node, base_path('app/Modules/Engenhocopolis/ferramentas/verificar-fases.mjs')]);
        $processo->run();

        $this->assertTrue($processo->isSuccessful(), $processo->getErrorOutput().$processo->getOutput());
        $this->assertStringContainsString('fases verificadas', $processo->getOutput());
    }
}

<?php

namespace Tests\Feature;

use App\Models\PortalApp;
use App\Models\User;
use App\Modules\Terminal\Models\Conquista;
use App\Modules\Terminal\Models\Diario;
use App\Modules\Terminal\Models\Resultado;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TerminalTest extends TestCase
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
        $this->artisan('migrate', ['--path' => 'app/Modules/Terminal/database/migrations/2026_10_13_100000_create_terminal_portal_app.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/Terminal/database/migrations/2026_10_13_110000_create_terminal_resultados_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/Terminal/database/migrations/2026_10_13_120000_add_modo_to_terminal_resultados_table.php']);
        foreach ([
            '2026_10_14_100000_add_estrelas_to_terminal_resultados_table.php',
            '2026_10_14_110000_create_terminal_diarios_table.php',
            '2026_10_14_120000_create_terminal_conquistas_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'app/Modules/Terminal/database/migrations/'.$migration]);
        }
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/terminal')->assertRedirect('/login');
    }

    public function test_usuario_logado_ve_o_jogo_com_pwa(): void
    {
        $app = PortalApp::where('start_link', '/terminal')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get('/terminal')
            ->assertOk()
            ->assertSee('terminal()', false)
            ->assertSee("/manifest/{$app->id}/manifest.json", false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('/images/apps/terminal.png', false);
    }

    public function test_migration_cria_o_app_privado_no_grupo_jogos(): void
    {
        $app = PortalApp::where('start_link', '/terminal')->firstOrFail();

        $this->assertSame('Terminal', $app->title);
        $this->assertSame('private', $app->visibility);
        $this->assertSame('Jogos', $app->package->name);
        $this->assertFileExists(public_path($app->icon));
        $this->assertNotNull($app->maskable_icon_file);
    }

    public function test_manifest_do_terminal_e_tela_cheia_retrato(): void
    {
        $app = PortalApp::where('start_link', '/terminal')->firstOrFail();

        $manifest = $this->get("/manifest/{$app->id}/manifest.json")->assertOk()->json();

        $this->assertSame('/terminal', $manifest['start_url']);
        $this->assertSame('/terminal', $manifest['scope']);
        $this->assertSame('fullscreen', $manifest['display']);
        $this->assertSame('portrait', $manifest['orientation']);
        $this->assertSame('#03120a', $manifest['theme_color']);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
    }

    public function test_tela_inicial_tem_run_configuracoes_e_funcionario_do_mes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/terminal')
            ->assertOk()
            ->assertSee('FUNCIONARIO DO MES')
            ->assertSee('CONFIGURACOES')
            ->assertSee('&gt; RUN', false);
    }

    public function test_registrar_guarda_so_o_melhor_tempo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 2, 'tempo_ms' => 30000])
            ->assertOk()->assertJson(['recorde' => true, 'tempo_ms' => 30000]);
        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 2, 'tempo_ms' => 45000])
            ->assertOk()->assertJson(['recorde' => false, 'tempo_ms' => 30000]);
        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 2, 'tempo_ms' => 20000])
            ->assertOk()->assertJson(['recorde' => true, 'tempo_ms' => 20000]);

        $this->assertSame(1, Resultado::count());
        $this->assertSame(20000, Resultado::first()->tempo_ms);
    }

    public function test_registrar_valida_os_dados(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 0, 'tempo_ms' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors(['fase', 'tempo_ms']);

        $this->postJson('/terminal/resultado', ['modo' => 'facil', 'fase' => 1, 'tempo_ms' => 5000])
            ->assertUnprocessable()->assertJsonValidationErrors(['modo']);
    }

    public function test_ranking_por_fase_geral_e_solucionadores(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $beto = User::factory()->create(['name' => 'Beto']);
        $caio = User::factory()->create(['name' => 'Caio']);

        // Ana: 2 fases (média 30 s); Beto: 1 fase rápida; Caio: 3 fases lentas.
        foreach ([[$ana, 1, 20000], [$ana, 2, 40000], [$beto, 1, 15000], [$caio, 1, 60000], [$caio, 2, 70000], [$caio, 3, 80000]] as [$u, $fase, $tempo]) {
            Resultado::create(['user_id' => $u->id, 'fase' => $fase, 'tempo_ms' => $tempo]);
        }

        $json = $this->actingAs($ana)->getJson('/terminal/ranking?fase=1')->assertOk()->json();

        $this->assertSame(3, $json['fase_maxima']);
        $this->assertSame(['Beto', 'Ana', 'Caio'], array_column($json['por_fase'], 'nome'));
        $this->assertSame([false, true, false], array_column($json['por_fase'], 'eu'));
        $this->assertSame(['Beto', 'Ana', 'Caio'], array_column($json['geral'], 'nome'));
        $this->assertSame(30000, $json['geral'][1]['tempo_ms']);
        $this->assertSame(['Caio', 'Ana', 'Beto'], array_column($json['solucionadores'], 'nome'));
        $this->assertSame(3, $json['solucionadores'][0]['fases']);
    }

    public function test_ranking_vazio_e_fase_fora_do_intervalo(): void
    {
        $json = $this->actingAs(User::factory()->create())->getJson('/terminal/ranking?fase=99')->assertOk()->json();

        $this->assertSame(1, $json['fase']);
        $this->assertSame([], $json['por_fase']);
        $this->assertSame([], $json['geral']);
    }

    public function test_ranking_e_resultados_sao_separados_por_modo(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $beto = User::factory()->create(['name' => 'Beto']);

        $this->actingAs($ana)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 1, 'tempo_ms' => 20000])->assertOk();
        $this->actingAs($ana)->postJson('/terminal/resultado', ['modo' => 'hard', 'fase' => 1, 'tempo_ms' => 50000])->assertOk()
            ->assertJson(['recorde' => true]);
        $this->actingAs($beto)->postJson('/terminal/resultado', ['modo' => 'hard', 'fase' => 1, 'tempo_ms' => 40000])->assertOk();

        $this->assertSame(3, Resultado::count());

        $normal = $this->actingAs($ana)->getJson('/terminal/ranking?modo=normal&fase=1')->json();
        $this->assertSame(['Ana'], array_column($normal['por_fase'], 'nome'));

        $hard = $this->actingAs($ana)->getJson('/terminal/ranking?modo=hard&fase=1')->json();
        $this->assertSame('hard', $hard['modo']);
        $this->assertSame(['Beto', 'Ana'], array_column($hard['por_fase'], 'nome'));
        $this->assertSame(['Beto', 'Ana'], array_column($hard['geral'], 'nome'));
    }

    public function test_estrelas_guardam_a_melhor_e_voltam_no_ranking(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 1, 'tempo_ms' => 30000, 'estrelas' => 2])
            ->assertOk()->assertJson(['estrelas' => 2]);
        // Tempo pior, mas mais estrelas: as estrelas sobem, o tempo fica.
        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 1, 'tempo_ms' => 50000, 'estrelas' => 3])
            ->assertOk()->assertJson(['recorde' => false, 'tempo_ms' => 30000, 'estrelas' => 3]);
        // Menos estrelas não rebaixam.
        $this->actingAs($user)->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 1, 'tempo_ms' => 20000, 'estrelas' => 1])
            ->assertOk()->assertJson(['recorde' => true, 'estrelas' => 3]);

        $json = $this->actingAs($user)->getJson('/terminal/ranking?modo=normal')->json();
        $this->assertSame(3, $json['estrelas'][1]);
        $this->assertSame([], $json['conquistas']);

        $this->postJson('/terminal/resultado', ['modo' => 'normal', 'fase' => 1, 'tempo_ms' => 20000, 'estrelas' => 4])
            ->assertUnprocessable()->assertJsonValidationErrors(['estrelas']);
    }

    public function test_diario_guarda_o_melhor_tempo_do_dia_e_so_aceita_hoje(): void
    {
        Carbon::setTestNow('2026-10-14 10:00:00');
        $ana = User::factory()->create(['name' => 'Ana']);
        $beto = User::factory()->create(['name' => 'Beto']);

        $this->actingAs($ana)->postJson('/terminal/diario', ['modo' => 'normal', 'data' => '2026-10-14', 'tempo_ms' => 40000])
            ->assertOk()->assertJson(['recorde' => true, 'sequencia' => 1]);
        $this->actingAs($ana)->postJson('/terminal/diario', ['modo' => 'normal', 'data' => '2026-10-14', 'tempo_ms' => 50000])
            ->assertOk()->assertJson(['recorde' => false, 'tempo_ms' => 40000]);
        $this->actingAs($beto)->postJson('/terminal/diario', ['modo' => 'normal', 'data' => '2026-10-14', 'tempo_ms' => 30000])->assertOk();
        $this->actingAs($beto)->postJson('/terminal/diario', ['modo' => 'normal', 'data' => '2026-10-13', 'tempo_ms' => 30000])
            ->assertUnprocessable()->assertJsonValidationErrors(['data']);

        $this->assertSame(2, Diario::count());

        $json = $this->actingAs($ana)->getJson('/terminal/diario?modo=normal')->assertOk()->json();
        $this->assertSame('2026-10-14', $json['data']);
        $this->assertSame(['Beto', 'Ana'], array_column($json['ranking'], 'nome'));
        $this->assertSame([false, true], array_column($json['ranking'], 'eu'));
        $this->assertSame(40000, $json['meu_tempo_ms']);

        $hard = $this->actingAs($ana)->getJson('/terminal/diario?modo=hard')->json();
        $this->assertSame([], $hard['ranking']);
        $this->assertNull($hard['meu_tempo_ms']);

        Carbon::setTestNow();
    }

    public function test_sequencia_conta_dias_seguidos_e_sobrevive_a_hoje_ainda_nao_jogado(): void
    {
        Carbon::setTestNow('2026-10-14 10:00:00');
        $user = User::factory()->create();

        foreach (['2026-10-11', '2026-10-12', '2026-10-13'] as $dia) {
            Diario::create(['user_id' => $user->id, 'modo' => 'normal', 'data' => $dia, 'tempo_ms' => 30000]);
        }
        // Dia solto antes da lacuna não entra na conta.
        Diario::create(['user_id' => $user->id, 'modo' => 'hard', 'data' => '2026-10-09', 'tempo_ms' => 30000]);

        $this->actingAs($user)->getJson('/terminal/diario')->assertJson(['sequencia' => 3]);

        $this->postJson('/terminal/diario', ['modo' => 'hard', 'data' => '2026-10-14', 'tempo_ms' => 30000])
            ->assertOk()->assertJson(['sequencia' => 4]);

        Carbon::setTestNow('2026-10-17 10:00:00');
        $this->getJson('/terminal/diario')->assertJson(['sequencia' => 0]);

        Carbon::setTestNow();
    }

    public function test_conquista_e_idempotente_e_valida_o_codigo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/conquista', ['codigo' => 'ZERO_WASTE'])->assertOk()->assertJson(['novo' => true]);
        $this->postJson('/terminal/conquista', ['codigo' => 'ZERO_WASTE'])->assertOk()->assertJson(['novo' => false]);
        $this->postJson('/terminal/conquista', ['codigo' => 'INVENTADA'])->assertUnprocessable()->assertJsonValidationErrors(['codigo']);

        $this->assertSame(1, Conquista::count());
        $this->getJson('/terminal/ranking')->assertJson(['conquistas' => ['ZERO_WASTE']]);
    }

    public function test_tela_inicial_tem_diario_fases_conquistas_e_scan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/terminal')
            ->assertOk()
            ->assertSee('jogarDiario()', false)
            ->assertSee("tela = 'fases'", false)
            ->assertSee('CONQUISTAS')
            ->assertSee('trocarScan()', false)
            ->assertSee('pularBoot()', false)
            ->assertSee('TERMINAL BIOS V1.0')
            ->assertSee('const HOJE = "'.Carbon::today()->toDateString().'"', false);
    }
}

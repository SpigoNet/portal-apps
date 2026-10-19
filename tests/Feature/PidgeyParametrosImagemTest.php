<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\PortalApp;
use App\Models\User;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\ParametroImagem;
use App\Modules\Pidgey\Services\ImagemPersonagemService;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;
use App\Services\AI\AiDriverInterface;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PidgeyParametrosImagemTest extends TestCase
{
    private AiModel $modelo;

    private Persona $persona;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2025_10_05_035231_create_packages_table.php',
            '2025_10_05_035312_create_portal_apps_table.php',
            '2025_10_05_035349_create_portal_app_user_table.php',
            '2026_02_04_000002_add_role_to_portal_app_user.php',
            '2026_01_28_144103_wpa.php',
            '2025_12_02_193624_create_metricas_acessos_table.php',
            '2026_08_22_000001_create_pidgey_agendamentos_table.php',
            '2026_08_24_000001_add_intervalo_e_dias_to_pidgey_agendamentos_table.php',
            '2026_08_24_000002_add_datas_to_pidgey_agendamentos_table.php',
            '2026_08_25_000001_create_pidgey_conteudos_dinamicos_table.php',
            '2026_08_25_000003_add_sistema_to_pidgey_conteudos.php',
            '2026_03_02_000000_create_ai_management_tables.php',
            '2026_03_02_143000_add_active_and_defaults_to_ai_models.php',
            '2026_03_05_144500_add_api_key_to_ai_provedores_table.php',
            '2026_03_16_022500_centralize_ai_configuration.php',
            '2026_08_25_000004_add_ai_model_id_to_pidgey_agendamentos_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/Pidgey/database/migrations']);
        foreach ([
            '2026_06_25_000009_create_alfred_personas_table.php',
            '2026_08_20_000001_add_canal_telegram_to_alfred_personas_table.php',
            '2026_10_16_100000_add_aparencia_to_alfred_personas_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'app/Modules/Alfred/database/migrations/'.$migration]);
        }

        $provedor = AiProvider::create(['nome' => 'Codex', 'driver' => 'openai', 'base_url' => 'http://gateway', 'is_active' => true]);
        $this->modelo = AiModel::create(['ai_provedor_id' => $provedor->id, 'modelo_id_externo' => 'gpt-image', 'nome' => 'GPT Image', 'input_types' => ['text', 'image'], 'output_types' => ['image'], 'raw_data' => [], 'is_active' => true]);

        $this->persona = Persona::create([
            'name' => 'Nami',
            'slug' => 'nami',
            'canal' => 'telegram',
            'aparencia' => 'Jovem navegadora de cabelo laranja curto.',
            'active' => true,
        ]);
    }

    /** Gera com um driver falso e devolve as opções que chegaram até ele. */
    private function opcoesEnviadas(): array
    {
        $driver = new class implements AiDriverInterface
        {
            public array $opcoes = [];

            public function generateText(array $messages, array $options = []): ?string
            {
                return null;
            }

            public function generateImage(string $prompt, array $options = []): ?string
            {
                $this->opcoes = $options;

                return 'pidgey/nami.png';
            }
        };

        $provedores = $this->createMock(AiProviderService::class);
        $provedores->method('getModelForUserEntity')->willReturn($this->modelo);
        $provedores->method('getImageToImageProvider')->willReturn(null);
        $provedores->method('createImageDriver')->willReturn($driver);

        $interpretador = $this->createMock(InterpretadorPersonaService::class);
        $interpretador->method('descreverExpressao')->willReturn('sorrindo');

        $imagem = (new ImagemPersonagemService($interpretador, $provedores))->gerar($this->persona, 'Bom dia!');

        $this->assertSame('pidgey/nami.png', $imagem);

        return $driver->opcoes;
    }

    public function test_imagem_do_personagem_leva_os_parametros_do_provedor_configurados_no_pidgey(): void
    {
        ParametroImagem::create([
            'ai_provedor_id' => $this->modelo->ai_provedor_id,
            'parametros' => ['quality' => 'medium', 'size' => '1024x1024', 'style' => 'invalido'],
        ]);

        $this->assertSame(['quality' => 'medium', 'size' => '1024x1024'], $this->opcoesEnviadas());
    }

    public function test_sem_parametros_configurados_nao_envia_opcoes(): void
    {
        $this->assertSame([], $this->opcoesEnviadas());
    }

    public function test_admin_do_pidgey_salva_e_limpa_os_parametros(): void
    {
        $app = PortalApp::create(['title' => 'Pidgey', 'description' => 'Mensageiro', 'icon' => '/images/apps/pidgey.png', 'start_link' => '/pidgey', 'visibility' => 'public']);
        $admin = User::factory()->create();
        DB::table('portal_app_user')->insert(['user_id' => $admin->id, 'portal_app_id' => $app->id, 'role' => 'admin']);

        $url = route('pidgey.parametros-imagem.update', $this->modelo->ai_provedor_id);

        $this->actingAs($admin)->put($url, ['quality' => 'high', 'output_format' => 'jpg', 'background' => ''])->assertRedirect();
        $this->assertSame(
            ['quality' => 'high', 'output_format' => 'jpeg'],
            ParametroImagem::firstWhere('ai_provedor_id', $this->modelo->ai_provedor_id)->parametros
        );

        $this->actingAs($admin)->get(route('pidgey.parametros-imagem.index'))->assertOk()->assertSee('Codex');

        $this->actingAs($admin)->put($url, [])->assertRedirect();
        $this->assertSame(0, ParametroImagem::count());
    }

    public function test_quem_nao_e_admin_nao_altera_os_parametros(): void
    {
        PortalApp::create(['title' => 'Pidgey', 'description' => 'Mensageiro', 'icon' => '/images/apps/pidgey.png', 'start_link' => '/pidgey', 'visibility' => 'public']);

        $this->actingAs(User::factory()->create())
            ->put(route('pidgey.parametros-imagem.update', $this->modelo->ai_provedor_id), ['quality' => 'high']);

        $this->assertSame(0, ParametroImagem::count());
    }
}

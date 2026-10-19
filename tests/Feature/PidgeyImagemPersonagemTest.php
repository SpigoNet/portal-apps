<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\User;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\EnvioService;
use App\Modules\Pidgey\Services\ImagemPersonagemService;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;
use App\Modules\Pidgey\Services\OllamaService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PidgeyImagemPersonagemTest extends TestCase
{
    private User $usuario;

    private Persona $persona;

    private object $gerador;

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

        Storage::fake('public');
        Storage::disk('public')->put('pidgey/nami-feliz.png', 'PNG');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->gerador = new class extends ImagemPersonagemService
        {
            public ?string $imagem = 'pidgey/nami-feliz.png';

            public array $mensagens = [];

            public function __construct() {}

            public function gerar(Persona $persona, string $mensagem, ?AiModel $modeloTexto = null): ?string
            {
                $this->mensagens[] = $mensagem;

                return $this->imagem;
            }
        };
        $this->app->instance(ImagemPersonagemService::class, $this->gerador);

        $this->persona = Persona::create([
            'name' => 'Nami',
            'slug' => 'nami',
            'canal' => 'telegram',
            'telegram_token' => 'TOKEN',
            'telegram_chat_id' => '42',
            'aparencia' => 'Jovem navegadora de cabelo laranja curto, tatuagem azul no ombro, estilo anime.',
            'active' => true,
        ]);
        $this->usuario = User::factory()->create();
    }

    private function agendamento(bool $comImagem, string $mensagem = 'Hora de beber água!'): Agendamento
    {
        return Agendamento::create([
            'user_id' => $this->usuario->id,
            'persona_slug' => 'nami',
            'canal' => 'telegram',
            'mensagem' => $mensagem,
            'interpretar' => false,
            'enviar_imagem' => $comImagem,
            'frequencia' => 'una_vez',
            'hora' => '09:00',
            'proxima_execucao' => now(),
            'ativo' => true,
        ]);
    }

    public function test_agendamento_com_imagem_envia_foto_com_a_mensagem_como_legenda(): void
    {
        $resultado = app(EnvioService::class)->enviarAgendamento($this->agendamento(true));

        $this->assertTrue($resultado['ok']);
        $this->assertSame('pidgey/nami-feliz.png', $resultado['imagem']);
        $this->assertSame(['Hora de beber água!'], $this->gerador->mensagens);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendPhoto')
            && collect($r->data())->contains(fn ($parte) => ($parte['name'] ?? null) === 'caption' && $parte['contents'] === 'Hora de beber água!')
            && collect($r->data())->contains(fn ($parte) => ($parte['name'] ?? null) === 'photo' && $parte['contents'] === 'PNG'));
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage'));
    }

    public function test_agendamento_sem_imagem_envia_so_texto_e_nao_gera_imagem(): void
    {
        $resultado = app(EnvioService::class)->enviarAgendamento($this->agendamento(false));

        $this->assertTrue($resultado['ok']);
        $this->assertNull($resultado['imagem']);
        $this->assertSame([], $this->gerador->mensagens);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && $r['text'] === 'Hora de beber água!');
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/sendPhoto'));
    }

    public function test_quando_a_imagem_falha_envia_so_o_texto(): void
    {
        $this->gerador->imagem = null;

        $resultado = app(EnvioService::class)->enviarAgendamento($this->agendamento(true));

        $this->assertTrue($resultado['ok']);
        $this->assertNull($resultado['imagem']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage'));
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/sendPhoto'));
    }

    public function test_persona_sem_aparencia_nao_tenta_gerar_imagem(): void
    {
        $this->persona->update(['aparencia' => null]);

        app(EnvioService::class)->enviarAgendamento($this->agendamento(true));

        $this->assertSame([], $this->gerador->mensagens);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage'));
    }

    public function test_legenda_longa_vai_em_mensagem_separada_apos_a_foto(): void
    {
        $longa = str_repeat('Lembrete importante. ', 60);

        app(EnvioService::class)->enviarAgendamento($this->agendamento(true, $longa));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendPhoto')
            && ! collect($r->data())->contains(fn ($parte) => ($parte['name'] ?? null) === 'caption'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && $r['text'] === $longa);
    }

    public function test_prompt_combina_aparencia_fixa_com_a_expressao_da_mensagem(): void
    {
        $servico = new ImagemPersonagemService(new InterpretadorPersonaService(new OllamaService), new AiProviderService);

        $prompt = $servico->montarPrompt($this->persona, 'smiling proudly, holding a glass of water');

        $this->assertStringContainsString('cabelo laranja curto', $prompt);
        $this->assertStringContainsString('smiling proudly, holding a glass of water', $prompt);
        $this->assertStringContainsString('No text', $prompt);
        $this->assertStringContainsString('reference image', $servico->montarPrompt($this->persona, 'sad', true));
    }

    public function test_formulario_salva_a_opcao_de_imagem_por_agendamento(): void
    {
        $this->actingAs($this->usuario)->post(route('pidgey.agendamentos.store'), [
            'persona_slug' => 'nami',
            'mensagem' => 'Com imagem',
            'frequencia' => 'diario',
            'hora' => '08:00',
            'enviar_imagem' => '1',
        ])->assertRedirect();

        $this->actingAs($this->usuario)->post(route('pidgey.agendamentos.store'), [
            'persona_slug' => 'nami',
            'mensagem' => 'Sem imagem',
            'frequencia' => 'diario',
            'hora' => '08:00',
        ])->assertRedirect();

        $this->assertTrue(Agendamento::where('mensagem', 'Com imagem')->first()->enviar_imagem);
        $this->assertFalse(Agendamento::where('mensagem', 'Sem imagem')->first()->enviar_imagem);
    }

    public function test_tela_de_edicao_mostra_os_dados_do_agendamento(): void
    {
        $agendamento = $this->agendamento(true, 'Mensagem original');

        $this->actingAs($this->usuario)->get(route('pidgey.agendamentos.edit', $agendamento))
            ->assertOk()
            ->assertSee('Mensagem original')
            ->assertSee('Salvar alterações');

        $this->actingAs($this->usuario)->get(route('pidgey.agendamentos.index'))
            ->assertOk()
            ->assertSee(route('pidgey.agendamentos.edit', $agendamento));
    }

    public function test_editar_altera_o_agendamento_e_recalcula_a_proxima_execucao(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 0));
        $agendamento = $this->agendamento(true, 'Antes');

        $this->actingAs($this->usuario)->put(route('pidgey.agendamentos.update', $agendamento), [
            'persona_slug' => 'nami',
            'mensagem' => 'Depois',
            'frequencia' => 'semanal',
            'dia_semana' => 5,
            'hora' => '18:30',
        ])->assertRedirect(route('pidgey.agendamentos.index'));

        $agendamento->refresh();
        $this->assertSame('Depois', $agendamento->mensagem);
        $this->assertSame('semanal', $agendamento->frequencia);
        $this->assertFalse($agendamento->enviar_imagem);
        $this->assertTrue($agendamento->ativo);
        $this->assertSame('2026-10-16 18:30', $agendamento->proxima_execucao->format('Y-m-d H:i'));
        $this->assertSame(1, Agendamento::withoutGlobalScopes()->count());
    }

    public function test_agendamento_pausado_continua_pausado_apos_editar(): void
    {
        $agendamento = $this->agendamento(false);
        $agendamento->update(['ativo' => false]);

        $this->actingAs($this->usuario)->put(route('pidgey.agendamentos.update', $agendamento), [
            'persona_slug' => 'nami',
            'mensagem' => 'Editado',
            'frequencia' => 'diario',
            'hora' => '08:00',
        ]);

        $this->assertFalse($agendamento->refresh()->ativo);
    }

    public function test_nao_edita_agendamento_de_outro_usuario(): void
    {
        $agendamento = $this->agendamento(false, 'Do outro');
        $intruso = User::factory()->create();

        $this->actingAs($intruso)->get(route('pidgey.agendamentos.edit', $agendamento))->assertNotFound();
        $this->actingAs($intruso)->put(route('pidgey.agendamentos.update', $agendamento), [
            'persona_slug' => 'nami',
            'mensagem' => 'Invadido',
            'frequencia' => 'diario',
        ])->assertNotFound();

        $this->assertSame('Do outro', $agendamento->refresh()->mensagem);
    }

    public function test_se_um_modelo_de_imagem_falha_tenta_o_proximo(): void
    {
        $quebrado = new AiModel(['nome' => 'Quebrado']);
        $quebrado->id = 1;
        $funciona = new AiModel(['nome' => 'Funciona']);
        $funciona->id = 2;

        $provedores = new class($quebrado, $funciona) extends AiProviderService
        {
            public array $tentativas = [];

            public function __construct(private AiModel $textoImagem, private AiModel $imagemImagem) {}

            public function getModelForUserEntity($user, string $inputType = 'image', string $outputType = 'image'): ?AiModel
            {
                return $this->textoImagem;
            }

            public function getImageToImageProvider($user = null): ?AiModel
            {
                return $this->imagemImagem;
            }

            public function supportsImageToImageProvider(?AiModel $provider): bool
            {
                return false;
            }

            public function createImageDriver(?AiModel $model): ?\App\Services\AI\AiDriverInterface
            {
                $this->tentativas[] = $model->nome;
                $url = $model->nome === 'Funciona' ? 'pidgey/nami-feliz.png' : null;

                return new class($url) implements \App\Services\AI\AiDriverInterface
                {
                    public function __construct(private ?string $url) {}

                    public function generateText(array $messages, array $options = []): ?string
                    {
                        return null;
                    }

                    public function generateImage(string $prompt, array $options = []): ?string
                    {
                        return $this->url;
                    }
                };
            }
        };

        $interpretador = new class extends InterpretadorPersonaService
        {
            public function __construct() {}

            public function descreverExpressao(Persona $persona, string $mensagem, ?AiModel $aiModel = null): string
            {
                return 'happy';
            }
        };

        $imagem = (new ImagemPersonagemService($interpretador, $provedores))->gerar($this->persona, 'Oi!');

        $this->assertSame('pidgey/nami-feliz.png', $imagem);
        $this->assertSame(['Quebrado', 'Funciona'], $provedores->tentativas);
    }
}

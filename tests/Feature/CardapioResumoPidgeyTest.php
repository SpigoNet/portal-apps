<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Services\Telegram\AgendamentoResumo;
use App\Modules\Cardapio\Services\Telegram\VozPersona;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\TelegramVinculo;
use App\Modules\Pidgey\Services\EnvioService;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CardapioResumoPidgeyTest extends TestCase
{
    private User $usuario;

    private Casa $casa;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2025_10_05_035231_create_packages_table.php',
            '2025_10_05_035312_create_portal_apps_table.php',
            '2026_01_28_144103_wpa.php',
            '2025_12_02_193624_create_metricas_acessos_table.php',
            '2026_08_22_000001_create_pidgey_agendamentos_table.php',
            '2026_08_24_000001_add_intervalo_e_dias_to_pidgey_agendamentos_table.php',
            '2026_08_24_000002_add_datas_to_pidgey_agendamentos_table.php',
            '2026_08_25_000001_create_pidgey_conteudos_dinamicos_table.php',
            '2026_08_25_000003_add_sistema_to_pidgey_conteudos.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/Cardapio/database/migrations']);
        $this->artisan('migrate', ['--path' => 'app/Modules/Pidgey/database/migrations']);
        $this->artisan('migrate', ['--path' => 'app/Modules/Alfred/database/migrations/2026_06_25_000009_create_alfred_personas_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/Alfred/database/migrations/2026_08_20_000001_add_canal_telegram_to_alfred_personas_table.php']);

        $this->travelTo(Carbon::parse('2026-10-14 10:00'));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->app->instance(VozPersona::class, new class extends VozPersona
        {
            public function __construct() {}

            public ?string $narracao = null;

            public string $fatos = '';

            public function falar(Persona $persona, string $frase): string
            {
                return $frase;
            }

            public function abrir(Persona $persona, string $fatos): ?string
            {
                $this->fatos = $fatos;

                return $this->narracao;
            }
        });

        Persona::updateOrCreate(['slug' => 'sanji'], ['name' => 'Sanji', 'telegram_token' => 'TOKEN', 'active' => true]);
        $this->usuario = User::factory()->create();
        $this->casa = Casa::create(['nome' => 'Casa teste']);
    }

    private function vincular(): void
    {
        TelegramVinculo::create(['user_id' => $this->usuario->id, 'chat_id' => '555', 'username' => 'gu']);
    }

    private function entrarNaCasa(): void
    {
        $this->casa->pessoas()->create(['user_id' => $this->usuario->id, 'nome' => 'Gustavo']);
    }

    public function test_vincular_o_telegram_cria_o_agendamento_diario_do_resumo(): void
    {
        $this->entrarNaCasa();

        $codigo = app(TelegramRoteador::class)->gerarCodigo($this->usuario);
        $this->postJson('/api/pidgey/webhook/bot/sanji', ['message' => ['chat' => ['id' => '555', 'type' => 'private'], 'from' => ['username' => 'gu'], 'text' => "/start {$codigo}"]], [
            'X-Telegram-Bot-Api-Secret-Token' => TelegramRoteador::segredo('sanji'),
        ])->assertOk();

        $this->assertDatabaseHas('pidgey_agendamentos', [
            'user_id' => $this->usuario->id, 'persona_slug' => 'sanji', 'frequencia' => 'diario', 'hora' => '07:00', 'ativo' => true,
        ]);
        $this->assertDatabaseHas('pidgey_conteudos_dinamicos', ['tipo' => 'resumo_cardapio', 'sistema' => true]);
    }

    public function test_entrar_numa_casa_com_telegram_ja_vinculado_cria_o_agendamento(): void
    {
        $this->vincular();
        $this->assertSame(0, Agendamento::withoutGlobalScopes()->count());

        $this->entrarNaCasa();

        $this->assertSame(1, Agendamento::withoutGlobalScopes()->count());
    }

    public function test_garantir_e_idempotente_e_exige_vinculo_e_casa(): void
    {
        $servico = app(AgendamentoResumo::class);

        $this->assertNull($servico->garantir($this->usuario->id));

        $this->vincular();
        $this->assertNull($servico->garantir($this->usuario->id)); // sem casa

        $this->entrarNaCasa();
        $servico->garantir($this->usuario->id);
        $servico->garantir($this->usuario->id);

        $this->assertSame(1, Agendamento::withoutGlobalScopes()->count());
    }

    public function test_envio_do_agendamento_manda_o_resumo_ao_chat_vinculado(): void
    {
        $this->vincular();
        $this->entrarNaCasa();
        $this->casa->itensEstoque()->create([
            'ingrediente_id' => $this->casa->ingredientes()->create(['nome' => 'Leite', 'unidade_base' => 'ml'])->id,
            'quantidade' => 500, 'local' => 'geladeira', 'validade' => today()->addDay(),
        ]);

        $agendamento = Agendamento::withoutGlobalScopes()->firstOrFail();
        $resultado = app(EnvioService::class)->enviarAgendamento($agendamento);

        $this->assertTrue($resultado['ok']);
        $this->assertTrue(Http::recorded(fn ($r) => str_contains($r->url(), 'botTOKEN/sendMessage')
            && (string) $r['chat_id'] === '555' && str_contains((string) $r['text'], 'Leite'))->isNotEmpty());
    }

    public function test_cardapio_nao_definido_vai_nos_fatos_e_no_resumo_anexado(): void
    {
        $this->vincular();
        $this->entrarNaCasa();

        $resultado = app(EnvioService::class)->enviarAgendamento(Agendamento::withoutGlobalScopes()->firstOrFail());

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('Cardápio não definido', $resultado['mensagem_final']);
        $this->assertStringContainsString('NÃO DEFINIDO', app(VozPersona::class)->fatos);
    }

    public function test_abertura_da_persona_vem_antes_do_resumo_completo(): void
    {
        $this->vincular();
        $this->entrarNaCasa();
        app(VozPersona::class)->narracao = 'Cadê o cardápio, hein?! Assim não dá!';

        $resultado = app(EnvioService::class)->enviarAgendamento(Agendamento::withoutGlobalScopes()->firstOrFail());

        $this->assertStringStartsWith("Cadê o cardápio, hein?! Assim não dá!\n\n🍽️ <b>Hoje</b>", $resultado['mensagem_final']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Services\Assistente\AssistenteService;
use App\Modules\Cardapio\Services\Telegram\SanjiTelegram;
use App\Modules\Cardapio\Services\Telegram\VozPersona;
use App\Modules\Pidgey\Models\TelegramVinculo;
use App\Modules\Pidgey\Services\TelegramRoteador;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CardapioTelegramTest extends TestCase
{
    private User $usuario;

    private Casa $casa;

    private Persona $persona;

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

        // A voz da persona não chama IA nos testes: devolve a frase como veio.
        $this->app->instance(VozPersona::class, new class extends VozPersona
        {
            public function __construct() {}

            public function falar(Persona $persona, string $frase): string
            {
                return $frase;
            }
        });

        $this->persona = Persona::create(['name' => 'Sanji', 'slug' => 'sanji', 'telegram_token' => 'TOKEN', 'active' => true]);
        $this->usuario = User::factory()->create();
        $this->casa = Casa::create(['nome' => 'Casa teste']);
        $this->casa->pessoas()->create(['user_id' => $this->usuario->id, 'nome' => 'Gustavo']);
    }

    private function webhook(array $update, ?string $segredo = null)
    {
        return $this->postJson('/api/pidgey/webhook/bot/sanji', $update, [
            'X-Telegram-Bot-Api-Secret-Token' => $segredo ?? TelegramRoteador::segredo('sanji'),
        ]);
    }

    private function msg(string $texto, string $chat = '555'): array
    {
        return ['message' => ['chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['username' => 'gu'], 'text' => $texto]];
    }

    private function enviadoContem(string $trecho): bool
    {
        return Http::recorded(fn ($req) => str_contains($req->url(), 'sendMessage') && str_contains((string) ($req['text'] ?? ''), $trecho))->isNotEmpty();
    }

    public function test_webhook_sem_segredo_correto_e_recusado(): void
    {
        $this->webhook($this->msg('/hoje'), 'errado')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_chat_nao_vinculado_nao_acessa_nada(): void
    {
        $this->webhook($this->msg('/compras'))->assertOk();

        $this->assertTrue($this->enviadoContem('não está vinculado'));
    }

    public function test_vinculo_por_codigo_de_uso_unico(): void
    {
        $codigo = app(TelegramRoteador::class)->gerarCodigo($this->usuario);

        $this->webhook($this->msg("/start {$codigo}"))->assertOk();

        $this->assertDatabaseHas('pidgey_telegram_vinculos', ['user_id' => $this->usuario->id, 'chat_id' => '555']);
        $this->assertTrue($this->enviadoContem('Vinculado'));

        // O mesmo código não vale uma segunda vez, nem para outro chat.
        $this->webhook($this->msg("/start {$codigo}", '999'))->assertOk();
        $this->assertDatabaseMissing('pidgey_telegram_vinculos', ['chat_id' => '999']);
    }

    public function test_codigo_invalido_nao_vincula(): void
    {
        $this->webhook($this->msg('/start INVALIDO'))->assertOk();

        $this->assertSame(0, TelegramVinculo::count());
        $this->assertTrue($this->enviadoContem('inválido ou expirado'));
    }

    public function test_grupos_sao_ignorados(): void
    {
        $update = $this->msg('/hoje');
        $update['message']['chat']['type'] = 'group';

        $this->webhook($update)->assertOk();

        Http::assertNothingSent();
    }

    public function test_comandos_mostram_dados_da_casa(): void
    {
        TelegramVinculo::create(['user_id' => $this->usuario->id, 'chat_id' => '555']);
        $arroz = $this->casa->ingredientes()->create(['nome' => 'Arroz', 'unidade_base' => 'g']);
        $this->casa->itensEstoque()->create(['ingrediente_id' => $arroz->id, 'local' => 'despensa', 'quantidade' => 2000, 'unidade' => 'g', 'validade' => today()->addDay()]);

        $this->webhook($this->msg('/estoque arroz'))->assertOk();
        $this->assertTrue($this->enviadoContem('Arroz'));

        $this->webhook($this->msg('/vencendo'))->assertOk();
        $this->assertTrue($this->enviadoContem('vence em 1 dia'));

        $this->webhook($this->msg('/hoje'))->assertOk();
        $this->assertTrue($this->enviadoContem('Não há nada planejado para hoje'));

        $this->webhook($this->msg('/compras'))->assertOk();
        $this->assertTrue($this->enviadoContem('Nada para comprar'));
    }

    public function test_texto_livre_propoe_e_botao_aplica_uma_unica_vez(): void
    {
        TelegramVinculo::create(['user_id' => $this->usuario->id, 'chat_id' => '555']);

        $pessoa = Pessoa::where('user_id', $this->usuario->id)->first();
        $assistente = Mockery::mock(AssistenteService::class);
        $assistente->shouldReceive('interpretar')->once()->andReturn([
            'mensagem' => 'Anotado!',
            'pendentes' => [['id' => 'a1', 'tipo' => 'x', 'resumo' => 'Comprar 2 kg de frango']],
            'respostas' => [],
            'rejeitadas' => [],
        ]);
        $assistente->shouldReceive('aplicar')->once()->withArgs(fn ($casa, $falante, $pend, $ids) => $ids === ['a1'] && $falante->id === $pessoa->id)
            ->andReturn([['id' => 'a1', 'resumo' => 'Comprar 2 kg de frango', 'ok' => true, 'texto' => 'Frango adicionado.']]);
        $this->app->instance(AssistenteService::class, $assistente);
        $this->app->forgetInstance(SanjiTelegram::class);
        $this->app->forgetInstance(TelegramRoteador::class);

        $this->webhook($this->msg('comprei 2 kg de frango'))->assertOk();

        $token = null;
        Http::recorded(function ($req) use (&$token) {
            $botao = $req['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? null;
            $botao && $token = $botao;
        });
        $this->assertNotNull($token, 'a proposta deve trazer o botão de aplicar');

        $toque = ['callback_query' => ['id' => 'cb1', 'data' => $token, 'message' => ['chat' => ['id' => '555']]]];
        $this->webhook($toque)->assertOk();
        $this->assertTrue($this->enviadoContem('Frango adicionado.'));

        // Tocar de novo não aplica outra vez.
        $this->webhook($toque)->assertOk();
        $this->assertTrue($this->enviadoContem('expirou'));
    }

    public function test_botao_de_outro_usuario_nao_aplica(): void
    {
        $outro = User::factory()->create();
        $outraCasa = Casa::create(['nome' => 'Outra']);
        $outraCasa->pessoas()->create(['user_id' => $outro->id, 'nome' => 'Intruso']);
        TelegramVinculo::create(['user_id' => $outro->id, 'chat_id' => '777']);

        \Illuminate\Support\Facades\Cache::put('cardapio.telegram.proposta.ABCDEFGHIJKLMNOP', [
            'pessoa_id' => Pessoa::where('user_id', $this->usuario->id)->value('id'),
            'pendentes' => [['id' => 'a1']],
        ], now()->addMinutes(5));

        $this->webhook(['callback_query' => ['id' => 'x', 'data' => 'cd:ap:ABCDEFGHIJKLMNOP', 'message' => ['chat' => ['id' => '777']]]])->assertOk();

        $this->assertTrue($this->enviadoContem('expirou'));
    }
}

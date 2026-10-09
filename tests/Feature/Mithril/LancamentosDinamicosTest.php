<?php

namespace Tests\Feature\Mithril;

use App\Models\User;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\Transacao;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use Tests\TestCase;

class LancamentosDinamicosTest extends TestCase
{
    private User $user;

    private Conta $conta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RegistrarAcesso::class);

        foreach ([
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_12_03_012907_create_mithril_tables.php',
        ] as $path) {
            $this->artisan('migrate', ['--path' => $path]);
        }

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->conta = Conta::create([
            'user_id' => $this->user->id,
            'nome' => 'Corrente',
            'tipo' => 'normal',
            'saldo_inicial' => 1000,
        ]);
    }

    private function criarPreTransacao(array $atributos = []): PreTransacao
    {
        return PreTransacao::create(array_merge([
            'user_id' => $this->user->id,
            'descricao' => 'Aluguel',
            'valor_parcela' => -300,
            'conta_id' => $this->conta->id,
            'dia_vencimento' => 10,
            'tipo' => 'recorrente',
            'data_inicio' => '2026-01-01',
        ], $atributos));
    }

    private function filtros(array $extra = []): array
    {
        return array_merge(['mes' => 10, 'ano' => 2026, 'conta_id' => $this->conta->id], $extra);
    }

    public function test_parcial_devolve_apenas_as_linhas_com_saldos(): void
    {
        $this->criarPreTransacao();

        $resposta = $this->get(route('mithril.lancamentos.index', $this->filtros(['_parcial' => 1])));

        $resposta->assertOk();
        $resposta->assertSee('Saldo Inicial do Período');
        $resposta->assertSee('Aluguel');
        $resposta->assertSee('data-acao="paguei-agora"', false);
        $resposta->assertDontSee('<html', false);
    }

    public function test_efetivar_respeita_data_escolhida_e_atualiza_linhas(): void
    {
        $pt = $this->criarPreTransacao();

        $this->postJson(route('mithril.pre-transacoes.efetivar', $pt->id), $this->filtros([
            'data_efetiva' => '2026-10-15',
        ]))->assertCreated();

        $transacao = Transacao::firstOrFail();
        $this->assertSame('2026-10-15', $transacao->data_efetiva->format('Y-m-d'));

        $this->get(route('mithril.lancamentos.index', $this->filtros(['_parcial' => 1])))
            ->assertSee('Efetivado')
            ->assertSee('data-acao="estornar"', false)
            ->assertDontSee('data-acao="paguei-agora"', false);
    }

    public function test_estornar_responde_json_e_remove_transacao(): void
    {
        $pt = $this->criarPreTransacao();
        $transacao = Transacao::create([
            'user_id' => $this->user->id,
            'conta_id' => $this->conta->id,
            'pre_transacao_id' => $pt->id,
            'descricao' => $pt->descricao,
            'valor' => -300,
            'data_efetiva' => '2026-10-10',
        ]);

        $this->postJson(route('mithril.transacoes.estornar', $transacao->id), $this->filtros())
            ->assertOk()
            ->assertJsonPath('data.pre_transacao_id', $pt->id);

        $this->assertDatabaseCount('mithril_transacoes', 0);
    }

    public function test_paguei_agora_via_json_cria_transacao(): void
    {
        $pt = $this->criarPreTransacao();

        $this->postJson(route('mithril.pre-transacoes.paguei-agora', $pt->id), $this->filtros(['valor' => -310]))
            ->assertCreated();

        $this->assertDatabaseHas('mithril_transacoes', ['pre_transacao_id' => $pt->id, 'valor' => -310]);
    }

    public function test_novo_lancamento_via_json_e_validacao(): void
    {
        $this->postJson(route('mithril.pre-transacoes.store'), [])->assertStatus(422);

        $this->postJson(route('mithril.pre-transacoes.store'), [
            'descricao' => 'Netflix',
            'valor_parcela' => '55,90',
            'conta_id' => $this->conta->id,
            'dia_vencimento' => 5,
            'tipo' => 'recorrente',
            'operacao' => 'debito',
        ])->assertCreated();

        $this->assertEquals(-55.90, PreTransacao::where('descricao', 'Netflix')->value('valor_parcela'));
    }
}

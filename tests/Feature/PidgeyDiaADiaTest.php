<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Models\UserProfile;
use App\Modules\Alfred\Services\Pidgey\ContextoDiaProvedor;
use App\Modules\Mithril\Models\CartaoFaturaItem;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Services\ContasPendentesService;
use App\Modules\Mithril\Services\Pidgey\ContasAPagarProvedor;
use App\Modules\Mithril\Services\Pidgey\RelatorioFinanceiroProvedor;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use App\Modules\Pidgey\Services\EnvioService;
use App\Modules\Pidgey\Services\ProvedoresConteudo;
use App\Modules\Pidgey\Services\ResumoFinanceiroService;
use App\Modules\Pidgey\Services\VozPersona;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Services\Pidgey\ResumoTarefasProvedor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PidgeyDiaADiaTest extends TestCase
{
    private User $usuario;

    private Persona $persona;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_12_03_012907_create_mithril_tables.php',
            'database/migrations/2025_11_14_133543_create_treetask_tables.php',
            'database/migrations/2026_08_22_000001_create_pidgey_agendamentos_table.php',
            'database/migrations/2026_08_24_000001_add_intervalo_e_dias_to_pidgey_agendamentos_table.php',
            'database/migrations/2026_08_24_000002_add_datas_to_pidgey_agendamentos_table.php',
            'database/migrations/2026_08_25_000001_create_pidgey_conteudos_dinamicos_table.php',
            'database/migrations/2026_08_25_000003_add_sistema_to_pidgey_conteudos.php',
            'app/Modules/Alfred/database/migrations/2026_06_25_000001_create_alfred_user_profiles_table.php',
            'app/Modules/Alfred/database/migrations/2026_06_25_000004_create_alfred_consumo_agua_table.php',
            'app/Modules/Alfred/database/migrations/2026_06_25_000005_create_alfred_rotinas_table.php',
            'app/Modules/Alfred/database/migrations/2026_06_25_000009_create_alfred_personas_table.php',
            'app/Modules/Alfred/database/migrations/2026_08_20_000001_add_canal_telegram_to_alfred_personas_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => $migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/Pidgey/database/migrations/2026_10_14_120000_create_pidgey_telegram_vinculos_table.php']);

        $this->travelTo(Carbon::parse('2026-10-15 08:00'));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        // A IA nunca é chamada: a abertura é fixa (e registrada para conferir os fatos).
        $this->app->instance(VozPersona::class, new class extends VozPersona
        {
            public function __construct() {}

            public ?string $abertura = 'Abertura da persona';

            public string $fatos = '';

            public function abrir(Persona $persona, string $fatos, ?string $instrucao = null): ?string
            {
                $this->fatos = $fatos;

                return $this->abertura;
            }
        });

        $this->usuario = User::factory()->create();
        $this->persona = Persona::create(['name' => 'Nami', 'slug' => 'nami', 'telegram_token' => 'TOKEN', 'telegram_chat_id' => '777', 'active' => true]);
    }

    private function agendamento(string $slug = 'nami'): Agendamento
    {
        return Agendamento::withoutGlobalScopes()->create([
            'user_id' => $this->usuario->id,
            'persona_slug' => $slug,
            'canal' => 'telegram',
            'mensagem' => '',
            'interpretar' => false,
            'frequencia' => 'diario',
            'hora' => '08:00',
            'ativo' => true,
        ]);
    }

    private function conta(): Conta
    {
        return Conta::create(['user_id' => $this->usuario->id, 'nome' => 'Corrente', 'tipo' => 'normal', 'saldo_inicial' => 0]);
    }

    private function despesa(Conta $conta, string $descricao, int $dia, array $extra = []): PreTransacao
    {
        return PreTransacao::create(array_merge([
            'user_id' => $this->usuario->id,
            'descricao' => $descricao,
            'valor_parcela' => -100,
            'conta_id' => $conta->id,
            'dia_vencimento' => $dia,
            'tipo' => 'recorrente',
            'data_inicio' => '2026-01-01',
            'ativa' => true,
        ], $extra));
    }

    public function test_contas_pendentes_separa_atrasadas_hoje_e_proximas(): void
    {
        $conta = $this->conta();
        $this->despesa($conta, 'Aluguel', 10);
        $this->despesa($conta, 'Internet', 15);
        $this->despesa($conta, 'Academia', 17);
        $this->despesa($conta, 'Seguro', 25);
        $this->despesa($conta, 'Luz paga', 5, ['data_ultima_acao' => '2026-10-05']);
        $this->despesa($conta, 'Parcelas acabadas', 12, ['tipo' => 'parcelada', 'total_parcelas' => 3, 'data_inicio' => '2026-01-01']);
        $this->despesa($conta, 'Salário', 12, ['valor_parcela' => 5000]);

        $grupos = app(ContasPendentesService::class)->para($this->usuario->id);

        $this->assertSame(['Aluguel'], $grupos['atrasadas']->pluck('descricao')->all());
        $this->assertSame(['Internet'], $grupos['hoje']->pluck('descricao')->all());
        $this->assertSame(['Academia'], $grupos['proximas']->pluck('descricao')->all());
    }

    private function cartao(string $nome, int $fechamento, int $vencimento): Conta
    {
        return Conta::create(['user_id' => $this->usuario->id, 'nome' => $nome, 'tipo' => 'credito', 'saldo_inicial' => 0, 'dia_fechamento' => $fechamento, 'dia_vencimento' => $vencimento]);
    }

    private function compra(Conta $cartao, string $data, float $valor): void
    {
        CartaoFaturaItem::create(['user_id' => $this->usuario->id, 'conta_id' => $cartao->id, 'descricao' => 'Compra', 'valor' => $valor, 'data_compra' => $data]);
    }

    public function test_contas_pendentes_inclui_so_a_fatura_ja_fechada_do_cartao(): void
    {
        $nubank = $this->cartao('Nubank', 8, 16);
        $this->compra($nubank, '2026-10-02', -300);
        $this->compra($nubank, '2026-09-20', -50);
        $this->compra($nubank, '2026-10-10', -200); // ciclo ainda aberto: fica de fora

        $grupos = app(ContasPendentesService::class)->para($this->usuario->id);

        $this->assertSame(['Fatura Nubank'], $grupos['proximas']->pluck('descricao')->all());
        $this->assertSame(350.0, $grupos['proximas']->first()['valor']);
        $this->assertSame('2026-10-16', $grupos['proximas']->first()['vencimento']->toDateString());
    }

    public function test_fatura_fechada_com_vencimento_passado_esta_atrasada_e_cartao_sem_divida_e_ignorado(): void
    {
        $atrasado = $this->cartao('Inter', 8, 12);
        $this->compra($atrasado, '2026-10-01', -120);
        $this->compra($this->cartao('Itaú', 8, 20), '2026-10-01', 40);

        $grupos = app(ContasPendentesService::class)->para($this->usuario->id);

        $this->assertSame(['Fatura Inter'], $grupos['atrasadas']->pluck('descricao')->all());
        $this->assertTrue($grupos['proximas']->isEmpty());
    }

    public function test_contas_a_pagar_nao_envia_nada_sem_pendencias(): void
    {
        $this->assertNull(app(ContasAPagarProvedor::class)->mensagem($this->agendamento(), $this->persona));
    }

    public function test_contas_a_pagar_monta_abertura_e_lista(): void
    {
        $this->despesa($this->conta(), 'Aluguel', 10);

        $texto = app(ContasAPagarProvedor::class)->mensagem($this->agendamento(), $this->persona);

        $this->assertStringContainsString('Abertura da persona', $texto);
        $this->assertStringContainsString('Atrasadas', $texto);
        $this->assertStringContainsString('Aluguel — R$ 100,00 (10/10)', $texto);
        $this->assertStringContainsString('Aluguel', app(VozPersona::class)->fatos);
    }

    public function test_relatorio_financeiro_sem_relatorio_nem_contas_nao_envia(): void
    {
        $this->app->instance(ResumoFinanceiroService::class, new class extends ResumoFinanceiroService
        {
            public function obterRelatorio(int $mes, int $ano): string
            {
                return '';
            }
        });

        $this->assertNull(app(RelatorioFinanceiroProvedor::class)->mensagem($this->agendamento(), $this->persona));

        $this->despesa($this->conta(), 'Aluguel', 10);

        $this->assertStringContainsString('Aluguel', (string) app(RelatorioFinanceiroProvedor::class)->mensagem($this->agendamento(), $this->persona));
    }

    private function tarefa(string $titulo, ?string $prazo, string $prioridade = 'Alta', string $status = 'A Fazer'): Tarefa
    {
        $projeto = Projeto::first() ?? Projeto::create(['nome' => 'Portal', 'descricao' => '-', 'id_user_owner' => $this->usuario->id]);
        $fase = Fase::first() ?? Fase::create(['id_projeto' => $projeto->id_projeto, 'nome' => 'Fase 1']);

        return Tarefa::create([
            'id_fase' => $fase->id_fase,
            'titulo' => $titulo,
            'status' => $status,
            'prioridade' => $prioridade,
            'id_user_responsavel' => $this->usuario->id,
            'data_vencimento' => $prazo,
        ]);
    }

    private function zoro(string $tipo): ResumoTarefasProvedor
    {
        return app()->make(ResumoTarefasProvedor::class, ['tipo' => $tipo]);
    }

    public function test_resumo_de_tarefas_lista_hoje_atrasadas_e_prioridades(): void
    {
        $this->tarefa('Entregar relatório', '2026-10-15 10:00:00', 'Urgente');
        $this->tarefa('Corrigir bug', '2026-10-13 10:00:00');
        $this->tarefa('Estudar Laravel', null, 'Média');
        $this->tarefa('Tarefa pronta', '2026-10-15 10:00:00', 'Alta', 'Concluído');

        $texto = $this->zoro(ResumoTarefasProvedor::TIPO_MANHA)->mensagem($this->agendamento('zoro'), $this->persona);

        $this->assertStringContainsString('Entregar relatório', $texto);
        $this->assertStringContainsString('Corrigir bug', $texto);
        $this->assertStringContainsString('Estudar Laravel', $texto);
        $this->assertStringNotContainsString('Tarefa pronta', $texto);
    }

    public function test_fechamento_so_cobra_o_que_ficou_aberto(): void
    {
        $this->assertNull($this->zoro(ResumoTarefasProvedor::TIPO_FECHAMENTO)->mensagem($this->agendamento('zoro'), $this->persona));

        $this->tarefa('Estudar Laravel', null, 'Média');
        $this->assertNull($this->zoro(ResumoTarefasProvedor::TIPO_FECHAMENTO)->mensagem($this->agendamento('zoro'), $this->persona));

        $this->tarefa('Corrigir bug', '2026-10-13 10:00:00');
        $texto = $this->zoro(ResumoTarefasProvedor::TIPO_FECHAMENTO)->mensagem($this->agendamento('zoro'), $this->persona);

        $this->assertStringContainsString('Corrigir bug', $texto);
        $this->assertStringNotContainsString('Estudar Laravel', $texto);
    }

    public function test_luffy_recebe_o_contexto_do_dia_e_tem_frase_de_reserva(): void
    {
        UserProfile::create(['user_id' => $this->usuario->id, 'energia_atual' => 'baixa', 'modo_dia_ruim' => true]);
        $this->tarefa('Corrigir bug', '2026-10-13 10:00:00');

        $voz = app(VozPersona::class);
        $texto = app(ContextoDiaProvedor::class)->mensagem($this->agendamento('luffy'), $this->persona);

        $this->assertSame('Abertura da persona', $texto);
        $this->assertStringContainsString('SIM', $voz->fatos);
        $this->assertStringContainsString('baixa', $voz->fatos);
        $this->assertStringContainsString('Corrigir bug', $voz->fatos);

        $voz->abertura = null;

        $this->assertStringContainsString('Bora conquistar hoje', app(ContextoDiaProvedor::class)->mensagem($this->agendamento('luffy'), $this->persona));
    }

    public function test_provedor_sem_vinculo_envia_para_o_chat_da_persona(): void
    {
        $this->despesa($this->conta(), 'Aluguel', 10);
        app(ProvedoresConteudo::class)->registrar(app(ContasAPagarProvedor::class));

        $agendamento = $this->agendamento();
        $agendamento->conteudosDinamicos()->attach(ConteudoDinamico::create(['nome' => 'Contas', 'tipo' => 'contas_a_pagar', 'ativo' => true, 'sistema' => true])->id);

        $resultado = app(EnvioService::class)->enviarAgendamento($agendamento->fresh());

        $this->assertTrue($resultado['ok']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && $request['chat_id'] === '777' && str_contains($request['text'], 'Aluguel'));
    }

    public function test_migration_cria_zoro_seus_agendamentos_e_troca_a_motivacao_do_luffy(): void
    {
        $texto = ConteudoDinamico::create(['nome' => 'Mensagem Motivacional (Luffy)', 'tipo' => 'texto', 'conteudo' => 'Bora!', 'ativo' => true]);
        $luffy = $this->agendamento('luffy');
        $luffy->conteudosDinamicos()->attach($texto->id);

        $this->artisan('migrate', ['--path' => 'app/Modules/Pidgey/database/migrations/2026_10_17_110000_cria_zoro_e_agendamentos_do_dia_a_dia.php']);

        $this->assertTrue(Persona::where('slug', 'zoro')->exists());

        $zoro = Agendamento::withoutGlobalScopes()->where('persona_slug', 'zoro')->orderBy('hora')->get();
        $this->assertSame(['08:30', '18:00'], $zoro->pluck('hora')->all());
        $this->assertSame([1, 2, 3, 4, 5], $zoro->first()->dias_semana);

        $this->assertSame(['contexto_dia'], $luffy->fresh()->conteudosDinamicos->pluck('tipo')->all());
    }
}

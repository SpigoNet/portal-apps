<?php

namespace Tests\Feature;

use App\Models\PortalApp;
use App\Models\User;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Support\Aparencia;
use Tests\TestCase;

class TreeTaskTelasTest extends TestCase
{
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_10_05_035231_create_packages_table.php',
            'database/migrations/2025_10_05_035312_create_portal_apps_table.php',
            'database/migrations/2025_10_05_035349_create_portal_app_user_table.php',
            'database/migrations/2026_02_04_000002_add_role_to_portal_app_user.php',
            'database/migrations/2026_01_28_144103_wpa.php',
            'database/migrations/2025_11_14_133543_create_treetask_tables.php',
            'database/migrations/2025_11_17_160222_add_treetask_ordem.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => $migration]);
        }

        $this->withoutMiddleware(RegistrarAcesso::class);
        $this->usuario = User::factory()->create(['name' => 'Gustavo Gomes']);
    }

    private function projetoComTarefas(): array
    {
        $projeto = Projeto::create(['nome' => 'Lançamento do site', 'descricao' => 'Nova home e contato', 'status' => 'Planejamento', 'id_user_owner' => $this->usuario->id]);
        $fase = Fase::create(['id_projeto' => $projeto->id_projeto, 'nome' => 'Conteúdo', 'ordem' => 0]);

        $base = ['id_fase' => $fase->id_fase, 'prioridade' => 'Alta', 'id_user_responsavel' => $this->usuario->id];
        $feita = Tarefa::create($base + ['titulo' => 'Definir paleta', 'status' => 'Concluído']);
        $aberta = Tarefa::create($base + ['titulo' => 'Escrever textos', 'status' => 'A Fazer', 'data_vencimento' => now()->subDays(2)]);

        return [$projeto, $fase, $feita, $aberta];
    }

    public function test_projetos_mostra_resumo_progresso_e_atrasos(): void
    {
        [$projeto] = $this->projetoComTarefas();

        $this->actingAs($this->usuario)
            ->get(route('treetask.index'))
            ->assertOk()
            ->assertSee('Lançamento do site')
            ->assertSee('1 de 2 tarefas concluídas')
            ->assertSee('50%')
            ->assertSee('1 atrasada')
            ->assertSee(route('treetask.lista', $projeto->id_projeto), false);
    }

    public function test_layout_tem_menu_lateral_e_troca_de_modulos(): void
    {
        PortalApp::create(['title' => 'Cardápio', 'description' => 'Cozinha', 'icon' => '/images/apps/cardapio.png', 'start_link' => '/cardapio', 'visibility' => 'public']);

        $this->actingAs($this->usuario)
            ->get(route('treetask.index'))
            ->assertOk()
            ->assertSee('Módulos do portal')
            ->assertSee('Cardápio')
            ->assertSeeInOrder(['Projetos', 'Kanban', 'Bom dia', 'Modo foco', 'Assistente IA']);
    }

    public function test_lista_do_projeto_mostra_fases_tarefas_e_proximos_vencimentos(): void
    {
        [$projeto] = $this->projetoComTarefas();

        $this->actingAs($this->usuario)
            ->get(route('treetask.lista', $projeto->id_projeto))
            ->assertOk()
            ->assertSeeInOrder(['Conteúdo', 'Definir paleta', 'Escrever textos'])
            ->assertSee('Próximos vencimentos')
            ->assertSee('Progresso geral');
    }

    public function test_lista_e_arvore_de_projeto_alheio_sao_proibidas(): void
    {
        $alheio = Projeto::create(['nome' => 'Alheio', 'descricao' => 'x', 'status' => 'Planejamento', 'id_user_owner' => User::factory()->create()->id]);

        $this->actingAs($this->usuario)->get(route('treetask.lista', $alheio->id_projeto))->assertForbidden();
        $this->actingAs($this->usuario)->get(route('treetask.tree.view', $alheio->id_projeto))->assertForbidden();
    }

    public function test_arvore_e_detalhe_da_tarefa(): void
    {
        [$projeto, , , $aberta] = $this->projetoComTarefas();

        $this->actingAs($this->usuario)
            ->get(route('treetask.tree.view', $projeto->id_projeto))
            ->assertOk()->assertSee('Objetivo principal')->assertSee('Escrever textos');

        $this->get(route('treetask.tarefas.show', $aberta->id_tarefa))
            ->assertOk()->assertSee('Escrever textos')->assertSee('Entrar em foco')->assertSee('Anexos');

        $this->get(route('treetask.tarefas.edit', $aberta->id_tarefa))
            ->assertOk()->assertSee('Editar tarefa')->assertSee('Salvar alterações');
    }

    public function test_foco_escolha_e_foco_total(): void
    {
        [, , , $aberta] = $this->projetoComTarefas();

        $this->actingAs($this->usuario)
            ->get(route('treetask.focus.index'))
            ->assertOk()->assertSee('O que vamos construir agora?')->assertSee('Escrever textos');

        $aberta->update(['status' => 'Em Andamento']);

        $this->get(route('treetask.focus.index'))
            ->assertOk()->assertSee('Concluir e comemorar')->assertSee('Sair do foco')->assertDontSee('O que vamos construir agora?');
    }

    public function test_bom_dia_celebracao_e_assistente(): void
    {
        [, , $feita] = $this->projetoComTarefas();

        $this->actingAs($this->usuario)
            ->get(route('treetask.good_morning'))
            ->assertOk()->assertSee('Lançamento do site')->assertSee('Próximas tarefas');

        $this->get(route('treetask.celebration.show', $feita->id_tarefa))
            ->assertOk()->assertSee('Tarefa concluída!')->assertSee('Escrever textos');

        $this->get(route('treetask.ai.index', ['type' => 'project', 'id' => 1]))
            ->assertOk()->assertSee('Assistente IA')->assertSee('Gerar ação')->assertSee('Contexto ativo');
    }

    public function test_sair_encerra_a_sessao(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('treetask.sair'))
            ->assertRedirect(route('welcome'));

        $this->assertGuest();
    }

    public function test_aparencia_traduz_status_prioridade_e_iniciais(): void
    {
        $this->assertSame('st-an', Aparencia::statusClasse('Em Andamento'));
        $this->assertSame('st-af', Aparencia::statusClasse('desconhecido'));
        $this->assertSame('p-urgente', Aparencia::prioridadeClasse('Urgente'));
        $this->assertSame('GG', Aparencia::iniciais('Gustavo Gomes'));
        $this->assertSame('MA', Aparencia::iniciais('Marina'));
        $this->assertSame('?', Aparencia::iniciais(null));
        $this->assertSame(Aparencia::corProjeto(3), Aparencia::corProjeto(3 + 8));
    }
}

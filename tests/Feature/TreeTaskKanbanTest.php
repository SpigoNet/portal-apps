<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Metricas\Http\Middleware\RegistrarAcesso;
use App\Modules\TreeTask\Models\Fase;
use App\Modules\TreeTask\Models\Projeto;
use App\Modules\TreeTask\Models\Tarefa;
use Tests\TestCase;

class TreeTaskKanbanTest extends TestCase
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
        $this->usuario = User::factory()->create();
    }

    private function projeto(?User $dono = null, string $nome = 'Site'): Projeto
    {
        return Projeto::create(['nome' => $nome, 'descricao' => 'x', 'status' => 'Planejamento', 'id_user_owner' => ($dono ?? $this->usuario)->id]);
    }

    private function tarefa(Projeto $projeto, string $titulo, array $extra = []): Tarefa
    {
        $fase = Fase::firstOrCreate(['id_projeto' => $projeto->id_projeto, 'nome' => 'Etapa 1'], ['ordem' => 0]);

        return Tarefa::create(array_merge([
            'id_fase' => $fase->id_fase,
            'titulo' => $titulo,
            'status' => 'A Fazer',
            'prioridade' => 'Média',
            'id_user_responsavel' => $this->usuario->id,
        ], $extra));
    }

    public function test_colunas_por_status_so_com_tarefas_do_usuario(): void
    {
        $meu = $this->projeto();
        $this->tarefa($meu, 'Minha a fazer');
        $this->tarefa($meu, 'Minha andando', ['status' => 'Em Andamento']);
        $this->tarefa($this->projeto(User::factory()->create(), 'Alheio'), 'Tarefa alheia');

        $resposta = $this->actingAs($this->usuario)->get(route('treetask.kanban'));

        $resposta->assertOk()
            ->assertSee('Modo foco')
            ->assertSeeInOrder(['Planejamento', 'A Fazer', 'Minha a fazer', 'Em Andamento', 'Minha andando', 'Aguardando resposta', 'Concluído'])
            ->assertDontSee('Tarefa alheia');
    }

    public function test_filtra_por_projeto_prioridade_busca_e_atrasadas(): void
    {
        $a = $this->projeto(null, 'Projeto A');
        $b = $this->projeto(null, 'Projeto B');
        $this->tarefa($a, 'Tarefa do A');
        $this->tarefa($b, 'Tarefa urgente do B', ['prioridade' => 'Urgente']);
        $this->tarefa($b, 'Tarefa velha', ['data_vencimento' => now()->subDays(3)]);

        $this->actingAs($this->usuario);

        $this->get(route('treetask.kanban', ['projeto' => [$a->id_projeto]]))
            ->assertSee('Tarefa do A')->assertDontSee('Tarefa urgente do B');

        $this->get(route('treetask.kanban', ['projeto' => [$a->id_projeto, $b->id_projeto]]))
            ->assertSee('Tarefa do A')->assertSee('Tarefa urgente do B');

        $this->get(route('treetask.kanban', ['prioridade' => 'Urgente']))
            ->assertSee('Tarefa urgente do B')->assertDontSee('Tarefa do A');

        $this->get(route('treetask.kanban', ['busca' => 'VELHA']))
            ->assertSee('Tarefa velha')->assertDontSee('Tarefa do A');

        $this->get(route('treetask.kanban', ['atrasadas' => 1]))
            ->assertSee('Tarefa velha')->assertDontSee('Tarefa do A');
    }

    public function test_concluidas_antigas_ficam_ocultas_ate_pedir_todas(): void
    {
        $projeto = $this->projeto();
        $velha = $this->tarefa($projeto, 'Concluída antiga', ['status' => 'Concluído']);
        Tarefa::where('id_tarefa', $velha->id_tarefa)->update(['updated_at' => now()->subDays(40)]);
        $this->tarefa($projeto, 'Concluída recente', ['status' => 'Concluído']);

        $this->actingAs($this->usuario);

        $this->get(route('treetask.kanban'))
            ->assertSee('Concluída recente')->assertDontSee('Concluída antiga');
        $this->get(route('treetask.kanban', ['todas_concluidas' => 1]))
            ->assertSee('Concluída antiga');
    }

    public function test_mover_cartao_muda_status_e_ordem(): void
    {
        $projeto = $this->projeto();
        $a = $this->tarefa($projeto, 'A');
        $b = $this->tarefa($projeto, 'B', ['status' => 'Em Andamento']);

        $this->actingAs($this->usuario)
            ->patchJson(route('treetask.kanban.mover', $a->id_tarefa), ['status' => 'Em Andamento', 'ids' => [$a->id_tarefa, $b->id_tarefa]])
            ->assertOk();

        $this->assertSame('Em Andamento', $a->fresh()->status);
        $this->assertSame(0, $a->fresh()->ordem);
        $this->assertSame(1, $b->fresh()->ordem);
    }

    public function test_nao_move_tarefa_de_outro_usuario_nem_status_invalido(): void
    {
        $alheia = $this->tarefa($this->projeto(User::factory()->create()), 'Alheia');

        $this->actingAs($this->usuario)
            ->patchJson(route('treetask.kanban.mover', $alheia->id_tarefa), ['status' => 'Concluído', 'ids' => [$alheia->id_tarefa]])
            ->assertForbidden();
        $this->assertSame('A Fazer', $alheia->fresh()->status);

        $minha = $this->tarefa($this->projeto(), 'Minha');
        $this->actingAs($this->usuario)
            ->patchJson(route('treetask.kanban.mover', $minha->id_tarefa), ['status' => 'Inventado', 'ids' => [$minha->id_tarefa]])
            ->assertUnprocessable();
    }

    public function test_pagina_do_projeto_redireciona_para_o_kanban_filtrado(): void
    {
        $projeto = $this->projeto();

        $this->actingAs($this->usuario)
            ->get(route('treetask.show', $projeto->id_projeto))
            ->assertRedirect(route('treetask.kanban', ['projeto' => [$projeto->id_projeto]]));
    }

    public function test_nova_tarefa_usa_primeira_fase_ou_cria_geral(): void
    {
        $projeto = $this->projeto();

        $this->actingAs($this->usuario)
            ->get(route('treetask.kanban.nova_tarefa', ['projeto' => $projeto->id_projeto]));

        $fase = Fase::where('id_projeto', $projeto->id_projeto)->firstOrFail();
        $this->assertSame('Geral', $fase->nome);
    }
}

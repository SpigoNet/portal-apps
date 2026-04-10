<?php

namespace Tests\Feature\GestorHoras;

use App\Models\User;
use App\Modules\GestorHoras\Models\Apontamento;
use App\Modules\GestorHoras\Models\Cliente;
use App\Modules\GestorHoras\Models\Contrato;
use App\Modules\GestorHoras\Models\ContratoItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContratoLivreFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Modules\Metricas\Http\Middleware\RegistrarAcesso::class);

        $this->createMinimalSchema();

        $this->resetData();
    }

    private function resetData(): void
    {
        DB::table('gh_apontamentos')->delete();
        DB::table('gh_contrato_itens')->delete();
        DB::table('gh_contratos')->delete();
        DB::table('gh_clientes')->delete();
        DB::table('portal_app_user')->delete();
        DB::table('portal_apps')->delete();
        DB::table('ant_professor_materia')->delete();
        DB::table('ant_configuracoes')->delete();
        DB::table('users')->delete();
    }

    private function createMinimalSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedBigInteger('gh_cliente_id')->nullable();
                $table->string('gh_role')->default('client');
                $table->string('google_id')->nullable();
                $table->string('microsoft_id')->nullable();
                $table->string('avatar')->nullable();
                $table->string('whatsapp_phone')->nullable();
                $table->string('whatsapp_apikey')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('portal_apps')) {
            Schema::create('portal_apps', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('icon')->nullable();
                $table->string('start_link')->nullable();
                $table->string('visibility')->default('private');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('portal_app_user')) {
            Schema::create('portal_app_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('portal_app_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gh_clientes')) {
            Schema::create('gh_clientes', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('documento')->nullable();
                $table->string('email_financeiro')->nullable();
                $table->string('access_token', 64)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gh_contratos')) {
            Schema::create('gh_contratos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gh_cliente_id');
                $table->string('titulo');
                $table->string('tipo')->default('fixo');
                $table->decimal('horas_contratadas', 8, 2);
                $table->decimal('valor_hora', 10, 2)->default(0);
                $table->date('data_inicio');
                $table->date('data_fim')->nullable();
                $table->string('status')->default('ativo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gh_contrato_itens')) {
            Schema::create('gh_contrato_itens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gh_contrato_id');
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->decimal('horas_estimadas', 8, 2)->default(0);
                $table->date('data_referencia')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gh_apontamentos')) {
            Schema::create('gh_apontamentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gh_contrato_id');
                $table->unsignedBigInteger('gh_contrato_item_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('descricao');
                $table->date('data_realizacao');
                $table->timestamp('iniciado_em')->nullable();
                $table->timestamp('finalizado_em')->nullable();
                $table->unsignedTinyInteger('apontamento_ativo')->nullable();
                $table->integer('minutos_gastos')->default(0);
                $table->string('faturamento_status')->default('nao_separado');
                $table->timestamp('faturamento_selecionado_em')->nullable();
                $table->unsignedBigInteger('faturamento_selecionado_por')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'apontamento_ativo'], 'gh_apontamentos_unico_ativo_por_usuario');
            });
        }

        if (!Schema::hasTable('ant_configuracoes')) {
            Schema::create('ant_configuracoes', function (Blueprint $table) {
                $table->id();
                $table->string('ia_driver')->nullable();
                $table->string('ia_key')->nullable();
                $table->string('ia_url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ant_professor_materia')) {
            Schema::create('ant_professor_materia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('semestre')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_operacional_can_create_livre_contract(): void
    {
        $user = $this->operacionalUser('admin');
        $cliente = Cliente::create([
            'nome' => 'Cliente Teste',
            'documento' => '123',
        ]);

        $response = $this->actingAs($user)->post(route('gestor-horas.store'), [
            'gh_cliente_id' => $cliente->id,
            'titulo' => 'Contrato Livre Teste',
            'tipo' => 'livre',
            'horas_contratadas' => 0,
            'valor_hora' => 185.50,
            'data_inicio' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('gestor-horas.index'));

        $this->assertDatabaseHas('gh_contratos', [
            'titulo' => 'Contrato Livre Teste',
            'tipo' => 'livre',
            'valor_hora' => 185.50,
        ]);
    }

    public function test_show_page_hides_saldo_for_livre_contract(): void
    {
        $user = $this->operacionalUser('dev');
        $contrato = $this->createContratoLivre();

        $response = $this->actingAs($user)->get(route('gestor-horas.show', $contrato->id));

        $response->assertOk();
        $response->assertDontSee('Saldo Disponível');
        $response->assertSee('Ciclo de Faturamento');
    }

    public function test_public_page_hides_saldo_for_livre_contract(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Cliente Publico',
            'documento' => '999',
        ]);
        $cliente->forceFill(['access_token' => Str::random(32)])->save();

        Contrato::create([
            'gh_cliente_id' => $cliente->id,
            'titulo' => 'Livre Publico',
            'tipo' => 'livre',
            'horas_contratadas' => 0,
            'valor_hora' => 150,
            'data_inicio' => now()->toDateString(),
            'status' => 'ativo',
        ]);

        $response = $this->get(route('gestor-horas.publico', $cliente->access_token));

        $response->assertOk();
        $response->assertDontSee('Saldo Restante');
        $response->assertSee('Aprovados/Faturados');
    }

    public function test_mobile_timer_allows_only_one_active_task_per_user(): void
    {
        $user = $this->operacionalUser('dev');
        $contrato = $this->createContratoLivre();

        $item = ContratoItem::create([
            'gh_contrato_id' => $contrato->id,
            'titulo' => 'Item mobile',
            'descricao' => 'Item para teste mobile',
            'horas_estimadas' => 8,
            'data_referencia' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('gestor-horas.mobile.start'), [
                'gh_contrato_id' => $contrato->id,
                'gh_contrato_item_id' => $item->id,
            ])
            ->assertRedirect(route('gestor-horas.mobile.timer'));

        $this->assertDatabaseHas('gh_apontamentos', [
            'gh_contrato_id' => $contrato->id,
            'gh_contrato_item_id' => $item->id,
            'user_id' => $user->id,
            'apontamento_ativo' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('gestor-horas.mobile.start'), [
                'gh_contrato_id' => $contrato->id,
                'gh_contrato_item_id' => $item->id,
            ])
            ->assertSessionHasErrors();

        $this->actingAs($user)
            ->post(route('gestor-horas.mobile.finish'), [
                'descricao' => 'Atividade finalizada em teste.',
            ])
            ->assertRedirect(route('gestor-horas.mobile.timer'));

        $apontamento = Apontamento::where('user_id', $user->id)->first();

        $this->assertNotNull($apontamento);
        $this->assertNull($apontamento->apontamento_ativo);
        $this->assertGreaterThan(0, $apontamento->minutos_gastos);
    }

    private function operacionalUser(string $perfil): User
    {
        $user = User::factory()->create();
        $user->forceFill(['gh_role' => $perfil])->save();

        return $user;
    }

    private function createContratoLivre(): Contrato
    {
        $cliente = Cliente::create([
            'nome' => 'Cliente Livre',
            'documento' => '321',
        ]);
        $cliente->forceFill(['access_token' => Str::random(32)])->save();

        return Contrato::create([
            'gh_cliente_id' => $cliente->id,
            'titulo' => 'Contrato Livre',
            'tipo' => 'livre',
            'horas_contratadas' => 0,
            'valor_hora' => 120,
            'data_inicio' => now()->toDateString(),
            'status' => 'ativo',
        ]);
    }
}

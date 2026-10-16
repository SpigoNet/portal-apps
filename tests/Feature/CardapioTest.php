<?php

namespace Tests\Feature;

use App\Models\PortalApp;
use App\Models\User;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use Tests\TestCase;

class CardapioTest extends TestCase
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

        $this->artisan('migrate', ['--path' => 'app/Modules/Cardapio/database/migrations']);
    }

    private function casaCom(User $usuario): Casa
    {
        $casa = Casa::create(['nome' => 'Casa teste']);
        $casa->pessoas()->create(['user_id' => $usuario->id, 'nome' => $usuario->name]);

        return $casa;
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/cardapio')->assertRedirect('/login');
    }

    public function test_migration_cria_o_app_privado_no_grupo_pessoal(): void
    {
        $app = PortalApp::where('start_link', '/cardapio')->firstOrFail();

        $this->assertSame('private', $app->visibility);
        $this->assertSame('Pessoal', $app->package?->name);
    }

    public function test_usuario_sem_casa_e_levado_a_criar_uma(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get('/cardapio')->assertRedirect('/cardapio/casa/criar');

        $this->actingAs($usuario)->post('/cardapio/casa', ['nome' => 'Lar'])->assertRedirect('/cardapio');

        $pessoa = Pessoa::where('user_id', $usuario->id)->firstOrFail();
        $this->assertSame('Lar', $pessoa->casa->nome);
        $this->actingAs($usuario)->get('/cardapio')->assertOk()->assertSee('Lar');
    }

    public function test_pessoa_sem_login_participa_da_rotina(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $filho = $casa->pessoas()->create(['nome' => 'Filho', 'tipo' => 'crianca']);
        $empregada = $casa->pessoas()->create(['nome' => 'Empregada']);

        $this->actingAs($usuario)->post('/cardapio/rotina', [
            'rotina' => ['almoco' => [$filho->id => [1, 2], $empregada->id => [1]]],
        ])->assertRedirect('/cardapio/rotina');

        $this->assertNull($filho->user_id);
        $this->assertSame(2, $filho->regrasRotina()->where('tipo_refeicao', 'almoco')->count());
        $this->assertSame(1, $empregada->regrasRotina()->count());
    }

    public function test_rotina_ignora_pessoa_de_outra_casa(): void
    {
        $dono = User::factory()->create();
        $this->casaCom($dono);
        $outro = User::factory()->create();
        $outraCasa = $this->casaCom($outro);
        $intrusa = $outraCasa->pessoas()->create(['nome' => 'Intrusa']);

        $this->actingAs($dono)->post('/cardapio/rotina', [
            'rotina' => ['almoco' => [$intrusa->id => [1]]],
        ]);

        $this->assertSame(0, $intrusa->regrasRotina()->count());
    }

    public function test_vincular_e_desvincular_usuario_mantem_a_pessoa_e_a_rotina(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $mariana = User::factory()->create();
        $pessoa = $casa->pessoas()->create(['nome' => 'Mariana']);
        $pessoa->regrasRotina()->create(['casa_id' => $casa->id, 'dia_semana' => 1, 'tipo_refeicao' => 'jantar']);

        $this->actingAs($dono)->put("/cardapio/pessoas/{$pessoa->id}", [
            'nome' => 'Mariana', 'tipo' => 'adulto', 'user_id' => $mariana->id,
        ])->assertRedirect('/cardapio/pessoas');
        $this->assertSame($mariana->id, $pessoa->fresh()->user_id);

        $this->actingAs($dono)->put("/cardapio/pessoas/{$pessoa->id}", [
            'nome' => 'Mariana', 'tipo' => 'adulto', 'user_id' => '',
        ]);
        $this->assertNull($pessoa->fresh()->user_id);
        $this->assertSame(1, $pessoa->regrasRotina()->count());
    }

    public function test_usuario_nao_pode_ser_vinculado_a_duas_pessoas(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $pessoa = $casa->pessoas()->create(['nome' => 'Outra']);

        $this->actingAs($dono)->put("/cardapio/pessoas/{$pessoa->id}", [
            'nome' => 'Outra', 'tipo' => 'adulto', 'user_id' => $dono->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_nao_acessa_pessoa_de_outra_casa(): void
    {
        $dono = User::factory()->create();
        $this->casaCom($dono);
        $outraCasa = $this->casaCom(User::factory()->create());
        $alheia = $outraCasa->pessoas()->first();

        $this->actingAs($dono)->get("/cardapio/pessoas/{$alheia->id}/edit")->assertNotFound();
    }

    public function test_nao_remove_a_si_mesmo(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $eu = $casa->pessoas()->where('user_id', $dono->id)->first();

        $this->actingAs($dono)->delete("/cardapio/pessoas/{$eu->id}")->assertSessionHasErrors('pessoa');
        $this->assertNotNull($eu->fresh());
    }

    public function test_telas_renderizam_com_pwa(): void
    {
        $usuario = User::factory()->create();
        $this->casaCom($usuario)->pessoas()->create(['nome' => 'Empregada']);
        $app = PortalApp::where('start_link', '/cardapio')->firstOrFail();

        $this->actingAs($usuario)->get('/cardapio/pessoas')->assertOk()->assertSee('Sem login', false)->assertSee("/manifest/{$app->id}/manifest.json", false);
        $this->actingAs($usuario)->get('/cardapio/pessoas/create')->assertOk();
        $this->actingAs($usuario)->get('/cardapio/rotina')->assertOk()->assertSee('Almoço');
    }

    public function test_ingredientes_sao_isolados_por_casa_e_nome_unico(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $outraCasa = $this->casaCom(User::factory()->create());
        $alheio = $outraCasa->ingredientes()->create(['nome' => 'Arroz', 'unidade_base' => 'g']);

        $this->actingAs($dono)->post('/cardapio/ingredientes', ['nome' => 'Arroz', 'unidade_base' => 'g'])
            ->assertRedirect('/cardapio/ingredientes');
        $this->actingAs($dono)->post('/cardapio/ingredientes', ['nome' => 'Arroz', 'unidade_base' => 'g'])
            ->assertSessionHasErrors('nome');
        $this->actingAs($dono)->get("/cardapio/ingredientes/{$alheio->id}/edit")->assertNotFound();
        $this->assertSame(1, $casa->ingredientes()->count());
    }

    public function test_adulto_edita_perfil_de_pessoa_sem_login(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $filho = $casa->pessoas()->create(['nome' => 'Filho', 'tipo' => 'crianca']);
        $banana = $casa->ingredientes()->create(['nome' => 'Banana', 'unidade_base' => 'un']);

        $this->actingAs($dono)->post("/cardapio/pessoas/{$filho->id}/preferencias", ['ingrediente_id' => $banana->id, 'nivel' => -5])
            ->assertSessionHasNoErrors();
        $this->actingAs($dono)->post("/cardapio/pessoas/{$filho->id}/restricoes", ['tipo' => 'alergia', 'ingrediente_id' => $banana->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(-5, $filho->preferencias()->first()->nivel);
        $this->assertSame('alergia', $filho->restricoes()->first()->tipo);
        $this->actingAs($dono)->get("/cardapio/pessoas/{$filho->id}/perfil")->assertOk()->assertSee('Banana');
    }

    public function test_crianca_com_login_so_edita_o_proprio_perfil(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $adulto = $casa->pessoas()->where('user_id', $dono->id)->first();
        $jovem = User::factory()->create();
        $pessoaJovem = $casa->pessoas()->create(['user_id' => $jovem->id, 'nome' => 'Jovem', 'tipo' => 'crianca']);

        $this->actingAs($jovem)->get("/cardapio/pessoas/{$pessoaJovem->id}/perfil")->assertOk();
        $this->actingAs($jovem)->get("/cardapio/pessoas/{$adulto->id}/perfil")->assertForbidden();
        $this->actingAs($jovem)->get('/cardapio/pessoas/create')->assertForbidden();
    }

    public function test_nao_usa_ingrediente_de_outra_casa_no_perfil(): void
    {
        $dono = User::factory()->create();
        $casa = $this->casaCom($dono);
        $eu = $casa->pessoas()->first();
        $alheio = $this->casaCom(User::factory()->create())->ingredientes()->create(['nome' => 'Leite', 'unidade_base' => 'ml']);

        $this->actingAs($dono)->post("/cardapio/pessoas/{$eu->id}/preferencias", ['ingrediente_id' => $alheio->id, 'nivel' => 3])->assertNotFound();
    }

    private function ingrediente(Casa $casa, string $nome, string $base = 'g'): \App\Modules\Cardapio\Models\Ingrediente
    {
        return $casa->ingredientes()->create(['nome' => $nome, 'unidade_base' => $base]);
    }

    public function test_estoque_converte_unidade_e_soma_lote_igual(): void
    {
        $casa = $this->casaCom(User::factory()->create());
        $arroz = $this->ingrediente($casa, 'Arroz');
        $servico = app(\App\Modules\Cardapio\Services\EstoqueService::class);

        $servico->adicionar($casa, $arroz, 2, 'kg', ['validade' => '2027-08-15']);
        $servico->adicionar($casa, $arroz, 500, 'g', ['validade' => '2027-08-15']);

        $this->assertSame(1, $casa->itensEstoque()->count());
        $this->assertSame(2500.0, $servico->total($casa, $arroz));
    }

    public function test_estoque_rejeita_unidade_incompativel(): void
    {
        $casa = $this->casaCom(User::factory()->create());
        $leite = $this->ingrediente($casa, 'Leite', 'ml');

        $this->expectException(\InvalidArgumentException::class);
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $leite, 1, 'kg');
    }

    public function test_estoque_nunca_fica_negativo(): void
    {
        $casa = $this->casaCom(User::factory()->create());
        $frango = $this->ingrediente($casa, 'Frango');
        $servico = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $servico->adicionar($casa, $frango, 500, 'g');

        try {
            $servico->baixar($casa, $frango, 800);
            $this->fail('Deveria recusar baixa maior que o estoque.');
        } catch (\App\Modules\Cardapio\Exceptions\EstoqueInsuficienteException) {
            $this->assertSame(500.0, $servico->total($casa, $frango));
        }

        $this->assertSame(500.0, $servico->baixar($casa, $frango, 800, limitar: true));
        $this->assertSame(0.0, $servico->total($casa, $frango));
        $this->assertSame(0, $casa->itensEstoque()->count());
    }

    public function test_baixa_consome_primeiro_o_lote_que_vence_antes(): void
    {
        $casa = $this->casaCom(User::factory()->create());
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        $servico = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $servico->adicionar($casa, $leite, 1, 'l', ['validade' => now()->addDays(30)->toDateString()]);
        $cedo = $servico->adicionar($casa, $leite, 1, 'l', ['validade' => now()->addDays(2)->toDateString(), 'local' => 'geladeira']);

        $servico->baixar($casa, $leite, 1000);

        $this->assertNull($cedo->fresh());
        $this->assertSame(1000.0, $servico->total($casa, $leite));
    }

    public function test_classificacao_de_validade(): void
    {
        $item = new \App\Modules\Cardapio\Models\ItemEstoque;
        foreach ([[-1, 'vencido'], [2, 'urgente'], [6, 'atencao'], [240, 'normal']] as [$dias, $esperado]) {
            $item->validade = now()->addDays($dias);
            $this->assertSame($esperado, $item->statusValidade(), "{$dias} dias");
        }
        $item->validade = null;
        $this->assertSame('normal', $item->statusValidade());
    }

    public function test_alerta_de_estoque_baixo(): void
    {
        $casa = $this->casaCom(User::factory()->create());
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $leite, 500, 'ml', ['quantidade_minima' => 2000]);

        $baixo = app(\App\Modules\Cardapio\Services\EstoqueService::class)->estoqueBaixo($casa);
        $this->assertCount(1, $baixo);
        $this->assertSame(500.0, $baixo->first()['total']);
    }

    public function test_tela_de_estoque_e_cadastro_pelo_formulario(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $arroz = $this->ingrediente($casa, 'Arroz');

        $this->actingAs($usuario)->post('/cardapio/estoque', [
            'ingrediente_id' => $arroz->id, 'quantidade' => 2, 'unidade' => 'kg', 'local' => 'despensa', 'validade' => now()->addDays(2)->toDateString(),
        ])->assertRedirect('/cardapio/estoque');

        $this->assertSame(2000.0, $casa->itensEstoque()->first()->quantidade);
        $this->actingAs($usuario)->get('/cardapio/estoque')->assertOk()->assertSee('Arroz')->assertSee('2 kg')->assertSee('vence em');
        $this->actingAs($usuario)->get('/cardapio/estoque/create')->assertOk();

        $item = $casa->itensEstoque()->first();
        $this->actingAs($usuario)->post("/cardapio/estoque/{$item->id}/baixar", ['quantidade' => 3, 'unidade' => 'kg'])->assertSessionHasErrors('quantidade');
        $this->actingAs($usuario)->post("/cardapio/estoque/{$item->id}/baixar", ['quantidade' => 500, 'unidade' => 'g']);
        $this->assertSame(1500.0, $item->fresh()->quantidade);
        $this->actingAs($usuario)->get("/cardapio/estoque/{$item->id}/edit")->assertOk();
    }

    public function test_estoque_de_outra_casa_e_inacessivel(): void
    {
        $dono = User::factory()->create();
        $this->casaCom($dono);
        $outra = $this->casaCom(User::factory()->create());
        $item = app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($outra, $this->ingrediente($outra, 'Sal'), 1, 'kg');

        $this->actingAs($dono)->get("/cardapio/estoque/{$item->id}/edit")->assertNotFound();
        $this->actingAs($dono)->delete("/cardapio/estoque/{$item->id}")->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosReceita(array $ingredientes, array $extra = []): array
    {
        return array_merge([
            'nome' => 'Strogonoff', 'rendimento' => 4, 'dificuldade' => 'media', 'frequencia' => 'alta',
            'categorias' => 'favorita, rapida', 'refeicoes_compativeis' => ['almoco', 'jantar'],
            'ingredientes' => $ingredientes,
        ], $extra);
    }

    public function test_cria_receita_com_conversao_de_unidades(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $frango = $this->ingrediente($casa, 'Frango');
        $creme = $this->ingrediente($casa, 'Creme de leite', 'ml');

        $this->actingAs($usuario)->post('/cardapio/receitas', $this->dadosReceita([
            ['ingrediente_id' => $frango->id, 'quantidade' => 0.5, 'unidade' => 'kg'],
            ['ingrediente_id' => $creme->id, 'quantidade' => 200, 'unidade' => 'ml', 'opcional' => '1'],
        ], ['favorita' => '1']))->assertRedirect();

        $receita = $casa->receitas()->firstOrFail();
        $this->assertTrue($receita->favorita);
        $this->assertSame(['favorita', 'rapida'], $receita->categorias);
        $this->assertSame([$frango->id => 500.0], $receita->necessidades(4));
        $this->assertSame([$frango->id => 500.0, $creme->id => 200.0], $receita->necessidades(4, incluirOpcionais: true));
        $this->actingAs($usuario)->get("/cardapio/receitas/{$receita->id}")->assertOk()->assertSee('Strogonoff')->assertSee('500 g');
        $this->actingAs($usuario)->get("/cardapio/receitas/{$receita->id}/edit")->assertOk();
    }

    public function test_receita_escala_pelo_numero_de_pessoas(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $frango = $this->ingrediente($casa, 'Frango');
        $this->actingAs($usuario)->post('/cardapio/receitas', $this->dadosReceita([
            ['ingrediente_id' => $frango->id, 'quantidade' => 500, 'unidade' => 'g'],
        ]));

        $receita = $casa->receitas()->firstOrFail()->load('ingredientes');
        $this->assertSame([$frango->id => 1250.0], $receita->necessidades(10));
        $this->actingAs($usuario)->get("/cardapio/receitas/{$receita->id}?porcoes=8")->assertSee('1 kg');
    }

    public function test_receita_recusa_unidade_incompativel_e_ingrediente_alheio(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $frango = $this->ingrediente($casa, 'Frango');
        $alheio = $this->ingrediente($this->casaCom(User::factory()->create()), 'Leite', 'ml');

        $this->actingAs($usuario)->post('/cardapio/receitas', $this->dadosReceita([
            ['ingrediente_id' => $frango->id, 'quantidade' => 1, 'unidade' => 'l'],
        ]))->assertSessionHasErrors();
        $this->actingAs($usuario)->post('/cardapio/receitas', $this->dadosReceita([
            ['ingrediente_id' => $alheio->id, 'quantidade' => 1, 'unidade' => 'l'],
        ]))->assertSessionHasErrors();
        $this->assertSame(0, $casa->receitas()->count());
    }

    public function test_substituicoes_de_ingrediente_e_favoritar(): void
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $creme = $this->ingrediente($casa, 'Creme de leite', 'ml');
        $semLactose = $this->ingrediente($casa, 'Creme sem lactose', 'ml');

        $this->actingAs($usuario)->put("/cardapio/ingredientes/{$creme->id}", [
            'nome' => 'Creme de leite', 'unidade_base' => 'ml', 'substitutos' => [$semLactose->id],
        ]);
        $this->assertSame([$semLactose->id], $creme->substituicoes()->pluck('substituto_id')->all());

        $this->actingAs($usuario)->put("/cardapio/ingredientes/{$creme->id}", ['nome' => 'Creme de leite', 'unidade_base' => 'ml']);
        $this->assertSame(0, $creme->substituicoes()->count());

        $receita = $casa->receitas()->create(['nome' => 'Panqueca']);
        $this->actingAs($usuario)->post("/cardapio/receitas/{$receita->id}/favoritar");
        $this->assertTrue($receita->fresh()->favorita);
    }

    public function test_receita_de_outra_casa_e_inacessivel(): void
    {
        $dono = User::factory()->create();
        $this->casaCom($dono);
        $alheia = $this->casaCom(User::factory()->create())->receitas()->create(['nome' => 'Segredo']);

        $this->actingAs($dono)->get("/cardapio/receitas/{$alheia->id}")->assertNotFound();
        $this->actingAs($dono)->delete("/cardapio/receitas/{$alheia->id}")->assertNotFound();
        $this->actingAs($dono)->get('/cardapio/receitas')->assertOk()->assertDontSee('Segredo');
    }

    /**
     * Casa com Gustavo (login), Filho e Empregada (sem login) e rotina de segunda: almoço p/ os três, jantar p/ o dono.
     *
     * @return array{0: User, 1: Casa}
     */
    private function casaComRotina(): array
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $eu = $casa->pessoas()->first();
        $filho = $casa->pessoas()->create(['nome' => 'Filho', 'tipo' => 'crianca']);
        $empregada = $casa->pessoas()->create(['nome' => 'Empregada']);

        foreach ([[$eu, 'almoco'], [$filho, 'almoco'], [$empregada, 'almoco'], [$eu, 'jantar']] as [$pessoa, $tipo]) {
            $casa->regrasRotina()->create(['pessoa_id' => $pessoa->id, 'dia_semana' => 1, 'tipo_refeicao' => $tipo]);
        }

        return [$usuario, $casa];
    }

    public function test_cria_semana_pela_rotina_incluindo_pessoas_sem_login(): void
    {
        [$usuario, $casa] = $this->casaComRotina();

        $this->actingAs($usuario)->post('/cardapio/planejamento', ['semana' => '2026-10-14'])->assertRedirect();

        $plano = $casa->planos()->firstOrFail();
        $this->assertSame('2026-10-12', $plano->inicio_semana->toDateString()); // segunda da semana
        $this->assertSame(2, $plano->refeicoes()->count());
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();
        $this->assertSame(3, $almoco->qtd_pessoas);
        $this->assertEqualsCanonicalizing(['Filho', 'Empregada', $usuario->name], $almoco->pessoas->pluck('nome')->all());

        // Recriar a mesma semana não duplica.
        $this->actingAs($usuario)->post('/cardapio/planejamento', ['semana' => '2026-10-16']);
        $this->assertSame(1, $casa->planos()->count());
        $this->assertSame(2, $plano->refeicoes()->count());
    }

    public function test_salva_receitas_e_participantes_do_cardapio(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $receita = $casa->receitas()->create(['nome' => 'Macarrão']);
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();
        $filho = $casa->pessoas()->where('nome', 'Filho')->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", [
            'receitas' => [$almoco->id => $receita->id],
            'pessoas' => [$almoco->id => [0, $filho->id]],
        ])->assertRedirect();

        $almoco->refresh();
        $this->assertSame($receita->id, $almoco->receita_id);
        $this->assertSame(1, $almoco->qtd_pessoas);
        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}")->assertOk()->assertSee('Macarrão')->assertSee('Seg, 12/10');
    }

    public function test_lista_de_compras_so_tem_o_deficit(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $frango = $this->ingrediente($casa, 'Frango');
        $arroz = $this->ingrediente($casa, 'Arroz');
        $receita = $casa->receitas()->create(['nome' => 'Frango com arroz', 'rendimento' => 1]);
        $receita->ingredientes()->createMany([
            ['ingrediente_id' => $frango->id, 'quantidade' => 500],
            ['ingrediente_id' => $arroz->id, 'quantidade' => 100],
        ]);
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $estoque->adicionar($casa, $frango, 1, 'kg');
        $estoque->adicionar($casa, $arroz, 1, 'kg');

        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $plano->refeicoes()->where('tipo_refeicao', 'almoco')->update(['receita_id' => $receita->id]); // 3 pessoas

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras")->assertRedirect();

        $lista = $casa->listasCompras()->firstOrFail();
        // Frango: precisa 1500 g, tem 1000 g → comprar 500 g. Arroz: precisa 300 g, tem 1000 g → nada.
        $this->assertSame(1, $lista->itens()->count());
        $item = $lista->itens()->first();
        $this->assertSame($frango->id, $item->ingrediente_id);
        $this->assertSame(1500.0, $item->quantidade_necessaria);
        $this->assertSame(1000.0, $item->quantidade_estoque);
        $this->assertSame(500.0, $item->quantidade_comprar);
        $this->actingAs($usuario)->get("/cardapio/compras/{$lista->id}")->assertOk()->assertSee('Comprar 500 g');
    }

    public function test_comprado_entra_no_estoque_e_nao_duplica_a_compra(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $frango = $this->ingrediente($casa, 'Frango');
        $receita = $casa->receitas()->create(['nome' => 'Frango', 'rendimento' => 1]);
        $receita->ingredientes()->create(['ingrediente_id' => $frango->id, 'quantidade' => 500]);
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $plano->refeicoes()->where('tipo_refeicao', 'almoco')->update(['receita_id' => $receita->id]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $lista = $casa->listasCompras()->firstOrFail();
        $item = $lista->itens()->first();
        $this->assertSame(1500.0, $item->quantidade_comprar);

        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens/{$item->id}/comprado");
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras"); // recalcula: comprado já conta
        $this->assertSame(1, $lista->itens()->count());

        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/estoque", ['local' => 'freezer'])->assertSessionHas('status');
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $this->assertSame(1500.0, $estoque->total($casa, $frango));
        $this->assertSame('freezer', $casa->itensEstoque()->first()->local);

        // Segunda chamada não soma de novo.
        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/estoque", ['local' => 'freezer']);
        $this->assertSame(1500.0, $estoque->total($casa, $frango));

        // Estoque cobre o cardápio: recalcular não gera nova compra.
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $this->assertSame(1, $lista->itens()->count());
        $this->assertTrue($lista->itens()->first()->comprado);
    }

    public function test_itens_manuais_sobrevivem_ao_recalculo_e_podem_ser_removidos(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $lista = $casa->listasCompras()->firstOrFail();

        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens", ['descricao' => 'Chocolate']);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");

        $item = $lista->itens()->where('manual', true)->firstOrFail();
        $this->assertSame('Chocolate', $item->descricao);
        $this->actingAs($usuario)->delete("/cardapio/compras/{$lista->id}/itens/{$item->id}");
        $this->assertSame(0, $lista->itens()->count());
    }

    public function test_marcar_refeicao_como_feita_baixa_o_estoque_sem_ficar_negativo(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $macarrao = $this->ingrediente($casa, 'Macarrão');
        $carne = $this->ingrediente($casa, 'Carne');
        $receita = $casa->receitas()->create(['nome' => 'Bolonhesa', 'rendimento' => 3]);
        $receita->ingredientes()->createMany([
            ['ingrediente_id' => $macarrao->id, 'quantidade' => 500],
            ['ingrediente_id' => $carne->id, 'quantidade' => 500],
        ]);
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $estoque->adicionar($casa, $macarrao, 2, 'kg');
        $estoque->adicionar($casa, $carne, 300, 'g'); // menos do que a receita pede

        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first(); // 3 pessoas = 1 rendimento
        $almoco->update(['receita_id' => $receita->id]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$almoco->id}/consumir")->assertSessionHas('status');
        $this->assertSame(1500.0, $estoque->total($casa, $macarrao));
        $this->assertSame(0.0, $estoque->total($casa, $carne));
        $this->assertNotNull($almoco->fresh()->consumida_em);

        // Idempotente: marcar de novo não baixa outra vez.
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$almoco->id}/consumir");
        $this->assertSame(1500.0, $estoque->total($casa, $macarrao));
    }

    public function test_consumir_exige_receita_e_planos_sao_isolados(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');
        $refeicao = $plano->refeicoes()->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/consumir")->assertSessionHasErrors('refeicao');

        $intruso = User::factory()->create();
        $this->casaCom($intruso);
        $this->actingAs($intruso)->get("/cardapio/planejamento/{$plano->id}")->assertNotFound();
        $this->actingAs($intruso)->post("/cardapio/planejamento/{$plano->id}/compras")->assertNotFound();
    }

    public function test_painel_mostra_hoje_e_alertas(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-12 10:00')); // segunda
        $receita = $casa->receitas()->create(['nome' => 'Lasanha']);
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, now());
        $plano->refeicoes()->where('tipo_refeicao', 'almoco')->update(['receita_id' => $receita->id]);
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $leite, 1, 'l', ['validade' => now()->addDay()->toDateString()]);

        $this->actingAs($usuario)->get('/cardapio')->assertOk()
            ->assertSee('Lasanha')
            ->assertSee('perto do vencimento')
            ->assertSee('próxima semana pendente');
    }

    /**
     * Receita de teste: nome => [[ingrediente, quantidade(base)], ...], serve 1 porção, compatível com o almoço.
     *
     * @param  array<int, array{0: \App\Modules\Cardapio\Models\Ingrediente, 1: float}>  $linhas
     */
    private function receita(Casa $casa, string $nome, array $linhas, array $extra = []): \App\Modules\Cardapio\Models\Receita
    {
        $receita = $casa->receitas()->create(array_merge(['nome' => $nome, 'rendimento' => 1, 'refeicoes_compativeis' => ['almoco']], $extra));
        foreach ($linhas as [$ingrediente, $quantidade]) {
            $receita->ingredientes()->create(['ingrediente_id' => $ingrediente->id, 'quantidade' => $quantidade]);
        }

        return $receita->load('ingredientes');
    }

    /**
     * Semana de 2026-10-12 (seg) só com o almoço de segunda e terça para o dono; devolve as refeições.
     *
     * @return array{0: User, 1: Casa, 2: \App\Modules\Cardapio\Models\PlanoSemanal}
     */
    private function semanaSimples(array $dias = [1]): array
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $eu = $casa->pessoas()->first();
        foreach ($dias as $dia) {
            $casa->regrasRotina()->create(['pessoa_id' => $eu->id, 'dia_semana' => $dia, 'tipo_refeicao' => 'almoco']);
        }
        $plano = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->criarSemana($casa, '2026-10-12');

        return [$usuario, $casa, $plano];
    }

    private function avaliar(\App\Modules\Cardapio\Models\Receita $receita, \App\Modules\Cardapio\Models\PlanoSemanal $plano): array
    {
        $refeicao = $plano->refeicoes()->with(['pessoas.restricoes', 'pessoas.preferencias'])->first();
        $servico = app(\App\Modules\Cardapio\Services\RecomendacaoService::class);

        return $servico->avaliar($receita, $refeicao, $servico->contexto($plano));
    }

    public function test_alergia_sem_substituto_exclui_a_receita(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $amendoim = $this->ingrediente($casa, 'Amendoim');
        $arroz = $this->ingrediente($casa, 'Arroz');
        $eu->restricoes()->create(['tipo' => 'alergia', 'ingrediente_id' => $amendoim->id]);
        $paco = $this->receita($casa, 'Paçoca', [[$amendoim, 100], [$arroz, 100]]);

        $a = $this->avaliar($paco, $plano);

        $this->assertFalse($a['compativel']);
        $this->assertStringContainsString('Amendoim', $a['avisos'][0]);
        $this->assertCount(0, app(\App\Modules\Cardapio\Services\PlanejadorService::class)->alternativas($plano->refeicoes()->first()));
    }

    public function test_intolerancia_e_adaptada_com_substituto_e_a_compra_usa_o_substituto(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $creme = $this->ingrediente($casa, 'Creme de leite', 'ml');
        $semLactose = $this->ingrediente($casa, 'Creme sem lactose', 'ml');
        $frango = $this->ingrediente($casa, 'Frango');
        $creme->substituicoes()->create(['casa_id' => $casa->id, 'substituto_id' => $semLactose->id]);
        $eu->restricoes()->create(['tipo' => 'intolerancia', 'ingrediente_id' => $creme->id]);
        $stroganoff = $this->receita($casa, 'Strogonoff', [[$frango, 500], [$creme, 200]]);

        $a = $this->avaliar($stroganoff, $plano);
        $this->assertTrue($a['compativel']);
        $this->assertSame([$creme->id => $semLactose->id], $a['substituicoes']);
        $this->assertStringContainsString('Creme sem lactose', implode(' ', $a['motivos']));

        $refeicao = $plano->refeicoes()->first();
        app(\App\Modules\Cardapio\Services\PlanejadorService::class)->escolher($refeicao, $stroganoff);
        $this->assertSame([$frango->id => 500.0, $semLactose->id => 200.0], $refeicao->fresh()->load('receita.ingredientes')->necessidades());

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $itens = $casa->listasCompras()->first()->itens()->pluck('quantidade_comprar', 'ingrediente_id')->all();
        $this->assertSame([$frango->id => 500.0, $semLactose->id => 200.0], $itens);
    }

    public function test_substituto_que_tambem_e_restrito_nao_serve(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        $soja = $this->ingrediente($casa, 'Bebida de soja', 'ml');
        $leite->substituicoes()->create(['casa_id' => $casa->id, 'substituto_id' => $soja->id]);
        $eu->restricoes()->createMany([
            ['tipo' => 'alergia', 'ingrediente_id' => $leite->id],
            ['tipo' => 'alergia', 'ingrediente_id' => $soja->id],
        ]);

        $this->assertFalse($this->avaliar($this->receita($casa, 'Panqueca', [[$leite, 300]]), $plano)['compativel']);
    }

    public function test_ingrediente_opcional_restrito_e_apenas_dispensado(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $nozes = $this->ingrediente($casa, 'Nozes');
        $arroz = $this->ingrediente($casa, 'Arroz');
        $eu->restricoes()->create(['tipo' => 'alergia', 'ingrediente_id' => $nozes->id]);
        $receita = $this->receita($casa, 'Arroz doce', [[$arroz, 100]]);
        $receita->ingredientes()->create(['ingrediente_id' => $nozes->id, 'quantidade' => 20, 'opcional' => true]);

        $a = $this->avaliar($receita->load('ingredientes'), $plano);
        $this->assertTrue($a['compativel']);
        $this->assertStringContainsString('Sem Nozes', $a['avisos'][0]);
    }

    public function test_estoque_pesa_a_favor_da_receita(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $frango = $this->ingrediente($casa, 'Frango');
        $peixe = $this->ingrediente($casa, 'Peixe');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $frango, 1, 'kg');
        $comEstoque = $this->receita($casa, 'Frango assado', [[$frango, 500]]);
        $semEstoque = $this->receita($casa, 'Peixe assado', [[$peixe, 500]]);

        $a = $this->avaliar($comEstoque, $plano);
        $b = $this->avaliar($semEstoque, $plano);

        $this->assertGreaterThan($b['score'], $a['score']);
        $this->assertStringContainsString('Já tem em casa: Frango', implode(' ', $a['motivos']));
        $this->assertStringContainsString('Falta comprar: Peixe', implode(' ', $b['avisos']));
    }

    public function test_validade_proxima_da_prioridade(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $tomate = $this->ingrediente($casa, 'Tomate');
        $cenoura = $this->ingrediente($casa, 'Cenoura');
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $estoque->adicionar($casa, $tomate, 500, 'g', ['validade' => now()->addDays(2)->toDateString()]);
        $estoque->adicionar($casa, $cenoura, 500, 'g', ['validade' => now()->addDays(90)->toDateString()]);

        $urgente = $this->avaliar($this->receita($casa, 'Molho', [[$tomate, 300]]), $plano);
        $folgado = $this->avaliar($this->receita($casa, 'Sopa', [[$cenoura, 300]]), $plano);

        $this->assertGreaterThan($folgado['score'], $urgente['score']);
        $this->assertStringContainsString('vence em 2 dia(s)', implode(' ', $urgente['motivos']));
    }

    public function test_vencido_nao_conta_como_estoque(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $leite, 1, 'l', ['validade' => now()->subDay()->toDateString()]);

        $a = $this->avaliar($this->receita($casa, 'Mingau', [[$leite, 300]]), $plano);

        $this->assertStringContainsString('Falta comprar: Leite', implode(' ', $a['avisos']));
    }

    public function test_favorita_e_preferencias_influenciam(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $massa = $this->ingrediente($casa, 'Macarrão');
        $figado = $this->ingrediente($casa, 'Fígado');
        $eu->preferencias()->create(['ingrediente_id' => $massa->id, 'nivel' => 5]);
        $eu->preferencias()->create(['ingrediente_id' => $figado->id, 'nivel' => -10]);

        $gosta = $this->avaliar($this->receita($casa, 'Macarronada', [[$massa, 100]]), $plano);
        $odeia = $this->avaliar($this->receita($casa, 'Fígado acebolado', [[$figado, 100]]), $plano);
        $fav = $this->avaliar($this->receita($casa, 'Lasanha', [[$this->ingrediente($casa, 'Queijo'), 100]], ['favorita' => true]), $plano);
        $comum = $this->avaliar($this->receita($casa, 'Omelete', [[$this->ingrediente($casa, 'Ovos', 'un'), 2]]), $plano);

        $this->assertGreaterThan($odeia['score'], $gosta['score']);
        $this->assertGreaterThan($comum['score'], $fav['score']);
        $this->assertStringContainsString('Favorita', implode(' ', $fav['motivos']));
    }

    public function test_uso_recente_reduz_a_pontuacao(): void
    {
        [, $casa, $plano] = $this->semanaSimples([1, 4]);
        $arroz = $this->ingrediente($casa, 'Arroz');
        $feijao = $this->ingrediente($casa, 'Feijão');
        $a = $this->receita($casa, 'Arroz de forno', [[$arroz, 100]]);
        $b = $this->receita($casa, 'Feijoada', [[$feijao, 100]]);

        $segunda = $plano->refeicoes()->orderBy('data')->first();
        $quinta = $plano->refeicoes()->orderBy('data')->get()->last();
        $segunda->update(['receita_id' => $a->id]);

        $servico = app(\App\Modules\Cardapio\Services\RecomendacaoService::class);
        $quinta->load('pessoas.restricoes', 'pessoas.preferencias');
        $ctx = $servico->contexto($plano, $quinta);

        $repetida = $servico->avaliar($a, $quinta, $ctx);
        $nova = $servico->avaliar($b, $quinta, $ctx);

        $this->assertGreaterThan($repetida['score'], $nova['score']);
        $this->assertStringContainsString('Usada há 3 dia(s)', implode(' ', $repetida['avisos']));
    }

    public function test_gerar_cardapio_preenche_com_justificativa_e_respeita_o_tipo_de_refeicao(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 2, 3]);
        $arroz = $this->ingrediente($casa, 'Arroz');
        $frango = $this->ingrediente($casa, 'Frango');
        $ovos = $this->ingrediente($casa, 'Ovos', 'un');
        $a = $this->receita($casa, 'Arroz com frango', [[$arroz, 100], [$frango, 200]]);
        $b = $this->receita($casa, 'Omelete', [[$ovos, 2]]);
        $jantarSo = $this->receita($casa, 'Sopa de jantar', [[$arroz, 50]], ['refeicoes_compativeis' => ['jantar']]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar")->assertRedirect()->assertSessionHas('status');

        $refeicoes = $plano->refeicoes()->get();
        $this->assertCount(3, $refeicoes);
        foreach ($refeicoes as $r) {
            $this->assertNotNull($r->receita_id);
            $this->assertNotSame($jantarSo->id, $r->receita_id);
            $this->assertNotNull($r->justificativa);
        }
        // Com só duas receitas válidas, a repetição empurra para alternar.
        $this->assertGreaterThanOrEqual(2, $refeicoes->pluck('receita_id')->unique()->count());
    }

    public function test_gerar_nao_sobrescreve_escolhas_manuais_mas_refazer_tudo_sobrescreve(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 2]);
        $x = $this->receita($casa, 'Receita X', [[$this->ingrediente($casa, 'A'), 100]]);
        $y = $this->receita($casa, 'Receita Y', [[$this->ingrediente($casa, 'B'), 100]]);
        $primeira = $plano->refeicoes()->orderBy('data')->first();
        $primeira->update(['receita_id' => $y->id]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");
        $this->assertSame($y->id, $primeira->fresh()->receita_id);
        $this->assertSame(0, $plano->refeicoes()->whereNull('receita_id')->count());

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar", ['tudo' => 1]);
        $this->assertSame(0, $plano->refeicoes()->whereNull('receita_id')->count());
        $this->assertNotNull($x->id);
    }

    public function test_semana_aproveita_ingrediente_perto_de_vencer_em_varias_refeicoes(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 2]);
        $frango = $this->ingrediente($casa, 'Frango');
        $peixe = $this->ingrediente($casa, 'Peixe');
        $arroz = $this->ingrediente($casa, 'Arroz');
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $estoque->adicionar($casa, $frango, 1, 'kg', ['validade' => now()->addDays(2)->toDateString()]);
        $estoque->adicionar($casa, $arroz, 1, 'kg');
        $assado = $this->receita($casa, 'Frango assado', [[$frango, 600]]);
        $arrozFrango = $this->receita($casa, 'Arroz de frango', [[$frango, 400], [$arroz, 100]]);
        $this->receita($casa, 'Peixe frito', [[$peixe, 400]]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");

        // 1 kg de frango a vencer cobre exatamente as duas receitas: gastar tudo antes do vencimento vence o peixe.
        $this->assertEqualsCanonicalizing([$assado->id, $arrozFrango->id], $plano->refeicoes()->pluck('receita_id')->all());
    }

    public function test_otimizacao_global_nunca_piora_a_semana_e_preserva_escolhas_manuais(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 2, 3]);
        foreach (['A', 'B', 'C', 'D'] as $i => $nome) {
            $this->receita($casa, "Receita {$nome}", [[$this->ingrediente($casa, "Ing {$nome}"), 100 * ($i + 1)]]);
        }
        $servico = app(\App\Modules\Cardapio\Services\PlanejadorService::class);
        $primeira = $plano->refeicoes()->orderBy('data')->first();
        $manual = $casa->receitas()->where('nome', 'Receita D')->first();
        $servico->escolher($primeira, $manual);

        $servico->gerar($plano);
        $antes = $servico->pontuacaoSemana($plano);
        $r = $servico->otimizar($plano);

        $this->assertGreaterThanOrEqual($antes - 0.01, $r['depois']);
        $this->assertEqualsWithDelta($servico->pontuacaoSemana($plano), $r['depois'], 0.01);
        $this->assertSame($manual->id, $primeira->fresh()->receita_id); // manual fica fixa
        $this->assertSame('manual', $primeira->fresh()->origem);
    }

    public function test_alternativas_ranqueadas_e_troca_recalcula_compras(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $frango = $this->ingrediente($casa, 'Frango');
        $peixe = $this->ingrediente($casa, 'Peixe');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $frango, 1, 'kg');
        $assado = $this->receita($casa, 'Frango assado', [[$frango, 500]]);
        $frito = $this->receita($casa, 'Peixe frito', [[$peixe, 500]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $frito->id]);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $this->assertSame(500.0, $casa->listasCompras()->first()->itens()->first()->quantidade_comprar);

        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/alternativas")
            ->assertOk()->assertSee('Frango assado')->assertDontSee('Peixe frito pts');

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/escolher", ['receita_id' => $assado->id])
            ->assertRedirect();

        $this->assertSame($assado->id, $refeicao->fresh()->receita_id);
        $this->assertNotNull($refeicao->fresh()->justificativa);
        $this->assertSame(0, $casa->listasCompras()->first()->itens()->count()); // estoque cobre: nada a comprar
    }

    public function test_regenerar_refeicao_nao_repete_a_receita_anterior(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $a = $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $b = $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $a->id]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/gerar")->assertRedirect();
        $this->assertSame($b->id, $refeicao->fresh()->receita_id);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/dia/2026-10-12/gerar")->assertRedirect();
        $this->assertSame($a->id, $refeicao->fresh()->receita_id);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/dia/2026-10-20/gerar")->assertNotFound();
    }

    public function test_gerar_informa_quando_nao_ha_receita_compativel(): void
    {
        [$usuario, , $plano] = $this->semanaSimples();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar")
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'sem receita compatível'));
        $this->assertNull($plano->refeicoes()->first()->receita_id);
    }

    public function test_refeicao_consumida_nao_e_alterada_pela_geracao(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $a = $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $a->id]);
        app(\App\Modules\Cardapio\Services\PlanejadorService::class)->consumir($refeicao->fresh());

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar", ['tudo' => 1]);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/gerar");
        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/alternativas")->assertNotFound();

        $this->assertSame($a->id, $refeicao->fresh()->receita_id);
    }

    public function test_salvar_manual_aplica_adaptacao_e_avisa_quando_incompativel(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $amendoim = $this->ingrediente($casa, 'Amendoim');
        $eu->restricoes()->create(['tipo' => 'alergia', 'ingrediente_id' => $amendoim->id]);
        $paco = $this->receita($casa, 'Paçoca', [[$amendoim, 100]]);
        $refeicao = $plano->refeicoes()->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", ['receitas' => [$refeicao->id => $paco->id]])
            ->assertSessionHasErrors();

        $this->assertSame($paco->id, $refeicao->fresh()->receita_id); // escolha manual é respeitada, com aviso
        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}")->assertOk()->assertSee('Por que esta receita?');
    }

    public function test_motor_nao_vaza_dados_entre_casas(): void
    {
        [$usuario, , $plano] = $this->semanaSimples();
        [$intruso, $casaB] = (function () {
            $u = User::factory()->create();

            return [$u, $this->casaCom($u)];
        })();
        $receitaB = $casaB->receitas()->create(['nome' => 'Receita secreta', 'refeicoes_compativeis' => ['almoco']]);
        $refeicao = $plano->refeicoes()->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/escolher", ['receita_id' => $receitaB->id])->assertNotFound();
        $this->actingAs($intruso)->post("/cardapio/planejamento/{$plano->id}/gerar")->assertNotFound();
        $this->actingAs($intruso)->get("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/alternativas")->assertNotFound();
    }

    private function marcarFeita(User $usuario, \App\Modules\Cardapio\Models\PlanoSemanal $plano, \App\Modules\Cardapio\Models\RefeicaoPlanejada $refeicao): \App\Modules\Cardapio\Models\HistoricoRefeicao
    {
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/consumir");

        return \App\Modules\Cardapio\Models\HistoricoRefeicao::where('refeicao_planejada_id', $refeicao->id)->firstOrFail();
    }

    public function test_marcar_como_feita_grava_o_historico_com_o_consumo_real(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $frango = $this->ingrediente($casa, 'Frango');
        $receita = $this->receita($casa, 'Frango assado', [[$frango, 500]]);
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $frango, 300, 'g'); // menos que o necessário
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $receita->id]);

        $historico = $this->marcarFeita($usuario, $plano, $refeicao);

        $this->assertSame($receita->id, $historico->receita_id);
        $this->assertSame('almoco', $historico->tipo_refeicao);
        $this->assertSame(1, $historico->qtd_pessoas);
        $this->assertSame(300.0, (float) $historico->consumo[$frango->id]); // só o que existia
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/consumir");
        $this->assertSame(1, $casa->historicos()->count()); // idempotente
    }

    public function test_historico_sobrevive_a_exclusao_do_plano(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $receita = $this->receita($casa, 'Qualquer', [[$this->ingrediente($casa, 'X'), 10]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $receita->id]);
        $this->marcarFeita($usuario, $plano, $refeicao);

        $this->actingAs($usuario)->delete("/cardapio/planejamento/{$plano->id}");

        $h = $casa->historicos()->firstOrFail();
        $this->assertNull($h->refeicao_planejada_id);
        $this->assertSame($receita->id, $h->receita_id);
    }

    public function test_feedback_e_validado_e_isolado_por_casa(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $receita = $this->receita($casa, 'Lasanha', [[$this->ingrediente($casa, 'Massa'), 10]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $receita->id]);
        $h = $this->marcarFeita($usuario, $plano, $refeicao);

        $this->actingAs($usuario)->post("/cardapio/historico/{$h->id}/feedback", ['feedback' => 'invalido'])->assertSessionHasErrors('feedback');
        $this->actingAs($usuario)->post("/cardapio/historico/{$h->id}/feedback", ['nota' => 9])->assertSessionHasErrors('nota');
        $this->actingAs($usuario)->post("/cardapio/historico/{$h->id}/feedback", ['feedback' => 'gostamos_muito', 'nota' => 5])->assertSessionHas('status');
        $this->assertSame('gostamos_muito', $h->fresh()->feedback);

        $intruso = User::factory()->create();
        $this->casaCom($intruso);
        $this->actingAs($intruso)->post("/cardapio/historico/{$h->id}/feedback", ['feedback' => 'normal'])->assertNotFound();
        $this->actingAs($usuario)->get('/cardapio/historico')->assertOk()->assertSee('Lasanha')->assertSee('Mais feitas');
    }

    public function test_valor_do_feedback_usa_a_nota_quando_nao_ha_feedback(): void
    {
        $h = new \App\Modules\Cardapio\Models\HistoricoRefeicao;
        $this->assertNull($h->valor());
        $h->nota = 5;
        $this->assertSame(1.0, $h->valor());
        $h->nota = 1;
        $this->assertSame(-1.0, $h->valor());
        $h->feedback = 'gostamos';
        $this->assertSame(0.5, $h->valor());
    }

    public function test_feedback_positivo_sobe_a_pontuacao_e_negativo_desce(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 4]);
        $a = $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $b = $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $c = $this->receita($casa, 'Receita C', [[$this->ingrediente($casa, 'C'), 100]]);
        $quinta = $plano->refeicoes()->orderBy('data')->get()->last();

        $antes = $this->avaliarEm($b, $quinta, $plano);
        foreach ([[$b, 'gostamos_muito'], [$c, 'nao_gostamos']] as [$receita, $fb]) {
            $casa->historicos()->create([
                'receita_id' => $receita->id, 'data' => '2026-09-01', 'tipo_refeicao' => 'almoco', 'consumida_em' => '2026-09-01 12:00', 'feedback' => $fb,
            ]);
        }

        $depoisB = $this->avaliarEm($b, $quinta, $plano);
        $depoisC = $this->avaliarEm($c, $quinta, $plano);
        $this->assertGreaterThan($antes['score'], $depoisB['score']);
        $this->assertStringContainsString('gostaram', implode(' ', $depoisB['motivos']));
        $this->assertLessThan($this->avaliarEm($a, $quinta, $plano)['score'], $depoisC['score']);
        $this->assertStringContainsString('não gostaram', implode(' ', $depoisC['avisos']));
    }

    private function avaliarEm(\App\Modules\Cardapio\Models\Receita $receita, \App\Modules\Cardapio\Models\RefeicaoPlanejada $refeicao, \App\Modules\Cardapio\Models\PlanoSemanal $plano): array
    {
        $refeicao->load('pessoas.restricoes', 'pessoas.preferencias');
        $servico = app(\App\Modules\Cardapio\Services\RecomendacaoService::class);

        return $servico->avaliar($receita->load('ingredientes'), $refeicao, $servico->contexto($plano, $refeicao));
    }

    public function test_nao_fazer_novamente_tira_a_receita_das_sugestoes_mas_nao_da_escolha_manual(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $ruim = $this->receita($casa, 'Fígado', [[$this->ingrediente($casa, 'Fígado cru'), 100]]);
        $boa = $this->receita($casa, 'Omelete', [[$this->ingrediente($casa, 'Ovos', 'un'), 2]]);
        $casa->historicos()->create(['receita_id' => $ruim->id, 'data' => '2026-09-01', 'tipo_refeicao' => 'almoco', 'consumida_em' => '2026-09-01 12:00', 'feedback' => 'nao_fazer_novamente']);
        $refeicao = $plano->refeicoes()->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");
        $this->assertSame($boa->id, $refeicao->fresh()->receita_id);
        $nomes = app(\App\Modules\Cardapio\Services\PlanejadorService::class)->alternativas($refeicao->fresh())->pluck('receita.nome')->all();
        $this->assertNotContains('Fígado', $nomes);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", ['receitas' => [$refeicao->id => $ruim->id]]);
        $this->assertSame($ruim->id, $refeicao->fresh()->receita_id);
        $this->assertStringContainsString('não fazer novamente', $refeicao->fresh()->justificativa);
        $this->actingAs($usuario)->get('/cardapio/historico')->assertSee('Não fazer novamente');
    }

    public function test_so_o_feedback_mais_recente_decide_o_bloqueio(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $receita = $this->receita($casa, 'Sopa', [[$this->ingrediente($casa, 'Legumes'), 100]]);
        $casa->historicos()->create(['receita_id' => $receita->id, 'data' => '2026-08-01', 'tipo_refeicao' => 'almoco', 'consumida_em' => '2026-08-01 12:00', 'feedback' => 'nao_fazer_novamente']);
        $casa->historicos()->create(['receita_id' => $receita->id, 'data' => '2026-09-01', 'tipo_refeicao' => 'almoco', 'consumida_em' => '2026-09-01 12:00', 'feedback' => 'gostamos']);

        $ctx = app(\App\Modules\Cardapio\Services\RecomendacaoService::class)->contexto($plano);
        $this->assertNotContains($receita->id, $ctx->bloqueadas);
    }

    public function test_registrar_sobra_cria_estoque_de_validade_curta(): void
    {
        [$usuario, $casa] = $this->semanaSimples();
        $receita = $this->receita($casa, 'Frango assado', [[$this->ingrediente($casa, 'Frango'), 500]]);

        $this->actingAs($usuario)->post('/cardapio/sobras', ['receita_id' => $receita->id, 'quantidade' => 400, 'unidade' => 'g', 'dias' => 2])
            ->assertRedirect('/cardapio/sobras');

        $ingrediente = $casa->ingredientes()->where('nome', 'Sobra de Frango assado')->firstOrFail();
        $this->assertTrue($ingrediente->sobra);
        $this->assertSame('g', $ingrediente->unidade_base);
        $lote = $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->firstOrFail();
        $this->assertSame(400.0, $lote->quantidade);
        $this->assertSame(now()->addDays(2)->toDateString(), $lote->validade->toDateString());
        $this->assertSame('geladeira', $lote->local);
        $this->assertSame(1, $casa->sobras()->count());

        // Mesma sobra, mesma validade: soma no mesmo lote.
        $this->actingAs($usuario)->post('/cardapio/sobras', ['receita_id' => $receita->id, 'quantidade' => 1, 'unidade' => 'kg', 'dias' => 2]);
        $this->assertSame(1, $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->count());
        $this->assertSame(1400.0, $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->first()->quantidade);

        $this->actingAs($usuario)->get('/cardapio/sobras')->assertOk()->assertSee('Sobra de Frango assado');
        $refeicao = \App\Modules\Cardapio\Models\PlanoSemanal::where('casa_id', $casa->id)->firstOrFail()->refeicoes()->firstOrFail();
        $this->actingAs($usuario)->get("/cardapio/sobras?refeicao={$refeicao->id}")->assertOk()->assertSee('Da refeição de');
        $this->actingAs($usuario)->get('/cardapio/estoque')->assertOk()->assertSee('sobra');
    }

    public function test_sobra_exige_nome_ou_receita_e_separa_unidades_base(): void
    {
        [$usuario, $casa] = $this->semanaSimples();

        $this->actingAs($usuario)->post('/cardapio/sobras', ['quantidade' => 100, 'unidade' => 'g', 'dias' => 2])->assertSessionHasErrors('nome');

        $this->actingAs($usuario)->post('/cardapio/sobras', ['nome' => 'sopa', 'quantidade' => 500, 'unidade' => 'g', 'dias' => 2]);
        $this->actingAs($usuario)->post('/cardapio/sobras', ['nome' => 'sopa', 'quantidade' => 500, 'unidade' => 'ml', 'dias' => 2]);

        $this->assertEqualsCanonicalizing(['Sobra de sopa', 'Sobra de sopa (ml)'], $casa->ingredientes()->pluck('nome')->all());
    }

    public function test_sobra_usada_em_receita_ganha_prioridade_por_vencer(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $this->actingAs($usuario)->post('/cardapio/sobras', ['nome' => 'frango', 'quantidade' => 400, 'unidade' => 'g', 'dias' => 1]);
        $sobra = $casa->ingredientes()->where('sobra', true)->firstOrFail();
        $sanduiche = $this->receita($casa, 'Sanduíche de frango', [[$sobra, 200]]);
        $outra = $this->receita($casa, 'Torrada', [[$this->ingrediente($casa, 'Pão'), 200]]);

        $a = $this->avaliar($sanduiche, $plano);
        $b = $this->avaliar($outra, $plano);

        $this->assertGreaterThan($b['score'], $a['score']);
        $this->assertStringContainsString('vence em 1 dia(s)', implode(' ', $a['motivos']));

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");
        $this->assertSame($sanduiche->id, $plano->refeicoes()->first()->receita_id);
    }

    public function test_consumir_receita_com_sobra_baixa_a_sobra_e_descartar_remove_o_lote(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $this->actingAs($usuario)->post('/cardapio/sobras', ['nome' => 'arroz', 'quantidade' => 500, 'unidade' => 'g', 'dias' => 2]);
        $sobra = $casa->ingredientes()->where('sobra', true)->firstOrFail();
        $receita = $this->receita($casa, 'Arroz de forno', [[$sobra, 300]]);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $receita->id]);

        $this->marcarFeita($usuario, $plano, $refeicao);
        $lote = $casa->itensEstoque()->where('ingrediente_id', $sobra->id)->firstOrFail();
        $this->assertSame(200.0, $lote->quantidade);

        $this->actingAs($usuario)->delete("/cardapio/sobras/{$lote->id}")->assertSessionHas('status');
        $this->assertSame(0, $casa->itensEstoque()->where('ingrediente_id', $sobra->id)->count());
    }

    public function test_sobras_e_estoque_comum_nao_se_misturam_entre_casas_nem_tipos(): void
    {
        [$usuario, $casa] = $this->semanaSimples();
        $comum = app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $this->ingrediente($casa, 'Arroz'), 1, 'kg');
        $outra = $this->casaCom(User::factory()->create());
        $this->actingAs(User::find($outra->pessoas()->first()->user_id))->post('/cardapio/sobras', ['nome' => 'x', 'quantidade' => 1, 'unidade' => 'g', 'dias' => 1]);
        $alheio = $outra->itensEstoque()->firstOrFail();

        $this->actingAs($usuario)->delete("/cardapio/sobras/{$comum->id}")->assertNotFound(); // não é sobra
        $this->actingAs($usuario)->delete("/cardapio/sobras/{$alheio->id}")->assertNotFound(); // outra casa
        $this->actingAs($usuario)->get('/cardapio/sobras')->assertOk()->assertDontSee('Sobra de x');
    }

    public function test_receita_mostra_frequencia_real_a_partir_do_historico(): void
    {
        [$usuario, $casa] = $this->semanaSimples();
        $receita = $this->receita($casa, 'Strogonoff', [[$this->ingrediente($casa, 'Frango'), 500]]);
        foreach (['2026-09-01', '2026-09-20'] as $data) {
            $casa->historicos()->create(['receita_id' => $receita->id, 'data' => $data, 'tipo_refeicao' => 'almoco', 'consumida_em' => $data.' 12:00', 'feedback' => 'gostamos_muito']);
        }

        $this->actingAs($usuario)->get("/cardapio/receitas/{$receita->id}")->assertOk()
            ->assertSee('Feita')->assertSee('20/09/2026')->assertSee('100%');
    }

    private function planejador(): \App\Modules\Cardapio\Services\PlanejadorService
    {
        return app(\App\Modules\Cardapio\Services\PlanejadorService::class);
    }

    // ---- F8: agenda (eventos e ausências) ----

    public function test_evento_fora_de_casa_tira_a_refeicao_da_grade(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $casa->eventos()->create(['data' => '2026-10-12', 'tipo_refeicao' => 'almoco', 'tipo' => 'fora', 'titulo' => 'Almoço na casa dos avós']);

        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');

        $this->assertSame(['jantar'], $plano->refeicoes()->pluck('tipo_refeicao')->all()); // só o jantar de segunda sobrou

        $casa->eventos()->create(['data' => '2026-10-12', 'tipo' => 'fora', 'titulo' => 'Viagem']); // dia todo
        $outro = $this->planejador()->sincronizar($plano);
        $this->assertSame(1, $outro['removidas']);
        $this->assertSame(0, $plano->refeicoes()->count());
    }

    public function test_convidados_aumentam_as_quantidades(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $frango = $this->ingrediente($casa, 'Frango');
        $receita = $this->receita($casa, 'Frango assado', [[$frango, 200]]);
        $casa->eventos()->create(['data' => '2026-10-12', 'tipo_refeicao' => 'almoco', 'tipo' => 'convidados', 'titulo' => 'Pais', 'pessoas_extras' => 3]);

        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();

        $this->assertSame(3, $almoco->pessoas->count());
        $this->assertSame(3, $almoco->pessoas_extras);
        $this->assertSame(6, $almoco->qtd_pessoas);
        $almoco->update(['receita_id' => $receita->id]);
        $this->assertSame([$frango->id => 1200.0], $almoco->fresh()->load('receita.ingredientes')->necessidades());
    }

    public function test_evento_criado_depois_ajusta_a_semana_e_recalcula_as_compras(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $frango = $this->ingrediente($casa, 'Frango');
        $receita = $this->receita($casa, 'Frango', [[$frango, 100]]);
        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');
        $plano->refeicoes()->where('tipo_refeicao', 'almoco')->update(['receita_id' => $receita->id]);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $lista = $casa->listasCompras()->first();
        $this->assertSame(300.0, $lista->itens()->first()->quantidade_comprar);

        $this->actingAs($usuario)->post('/cardapio/agenda/eventos', [
            'data' => '2026-10-12', 'tipo' => 'convidados', 'titulo' => 'Visita', 'pessoas_extras' => 2, 'tipo_refeicao' => 'almoco',
        ])->assertRedirect('/cardapio/agenda');

        $this->assertSame(5, $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first()->qtd_pessoas);
        $this->assertSame(500.0, $lista->itens()->first()->quantidade_comprar);

        $this->actingAs($usuario)->post('/cardapio/agenda/eventos', ['data' => '2026-10-12', 'tipo' => 'fora', 'titulo' => 'Restaurante', 'tipo_refeicao' => 'almoco']);
        $this->assertSame(0, $plano->refeicoes()->where('tipo_refeicao', 'almoco')->count());
        $this->assertSame(0, $lista->itens()->count());
    }

    public function test_evento_nao_mexe_em_refeicao_ja_feita_e_valida_convidados(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $receita = $this->receita($casa, 'Qualquer', [[$this->ingrediente($casa, 'X'), 10]]);
        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();
        $almoco->update(['receita_id' => $receita->id]);
        $this->planejador()->consumir($almoco->fresh());

        $this->actingAs($usuario)->post('/cardapio/agenda/eventos', ['data' => '2026-10-12', 'tipo' => 'fora', 'titulo' => 'Fora']);
        $this->assertNotNull($almoco->fresh()); // feita: fica
        $this->actingAs($usuario)->post('/cardapio/agenda/eventos', ['data' => '2026-10-12', 'tipo' => 'convidados', 'titulo' => 'Sem número'])->assertSessionHasErrors('pessoas_extras');
        $this->actingAs($usuario)->get('/cardapio/agenda')->assertOk()->assertSee('Fora');
    }

    public function test_ausencia_tira_a_pessoa_do_calculo_e_nao_afeta_dias_fora_do_periodo(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $eu = $casa->pessoas()->where('user_id', $usuario->id)->first();
        $casa->regrasRotina()->create(['pessoa_id' => $eu->id, 'dia_semana' => 4, 'tipo_refeicao' => 'jantar']);
        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');

        $this->actingAs($usuario)->post('/cardapio/agenda/ausencias', ['pessoa_id' => $eu->id, 'inicio' => '2026-10-12', 'fim' => '2026-10-13', 'motivo' => 'viagem'])
            ->assertRedirect('/cardapio/agenda');

        $segundaAlmoco = $plano->refeicoes()->whereDate('data', '2026-10-12')->where('tipo_refeicao', 'almoco')->first();
        $this->assertSame(2, $segundaAlmoco->qtd_pessoas);
        $this->assertFalse($segundaAlmoco->pessoas->contains('id', $eu->id));
        $this->assertSame(0, $plano->refeicoes()->whereDate('data', '2026-10-12')->where('tipo_refeicao', 'jantar')->count()); // só ele jantava
        $this->assertSame(1, $plano->refeicoes()->whereDate('data', '2026-10-15')->where('tipo_refeicao', 'jantar')->count()); // quinta: fora do período
    }

    public function test_ausencia_preserva_ajuste_manual_e_sincronizar_restaura(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $eu = $casa->pessoas()->where('user_id', $usuario->id)->first();
        $filho = $casa->pessoas()->where('nome', 'Filho')->first();
        $plano = $this->planejador()->criarSemana($casa, '2026-10-12');
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", ['pessoas' => [$almoco->id => [$eu->id, $filho->id]]]); // tirou a empregada
        $this->assertTrue($almoco->fresh()->pessoas_manual);
        $this->assertSame(2, $almoco->fresh()->qtd_pessoas);

        $this->actingAs($usuario)->post('/cardapio/agenda/ausencias', ['pessoa_id' => $filho->id, 'inicio' => '2026-10-12', 'fim' => '2026-10-12']);
        $this->assertEqualsCanonicalizing([$eu->id], $almoco->fresh()->pessoas->pluck('id')->all()); // manual mantido, só sai o ausente

        $ausencia = $casa->ausencias()->first();
        $this->actingAs($usuario)->delete("/cardapio/agenda/ausencias/{$ausencia->id}");
        $this->assertSame(1, $almoco->fresh()->pessoas->count()); // remover não recoloca sozinho

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/sincronizar")->assertSessionHas('status');
        $this->assertSame(3, $almoco->fresh()->pessoas->count());
        $this->assertFalse($almoco->fresh()->pessoas_manual);
    }

    public function test_ausencia_valida_periodo_e_pessoa_da_casa(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $alheia = $this->casaCom(User::factory()->create())->pessoas()->first();
        $eu = $casa->pessoas()->first();

        $this->actingAs($usuario)->post('/cardapio/agenda/ausencias', ['pessoa_id' => $eu->id, 'inicio' => '2026-10-14', 'fim' => '2026-10-12'])->assertSessionHasErrors('fim');
        $this->actingAs($usuario)->post('/cardapio/agenda/ausencias', ['pessoa_id' => $alheia->id, 'inicio' => '2026-10-12', 'fim' => '2026-10-13'])->assertNotFound();

        $estranho = $this->casaCom(User::factory()->create());
        $evento = $estranho->eventos()->create(['data' => '2026-10-12', 'tipo' => 'fora', 'titulo' => 'X']);
        $this->actingAs($usuario)->delete("/cardapio/agenda/eventos/{$evento->id}")->assertNotFound();
    }

    // ---- F8: produtos recorrentes e próxima compra ----

    public function test_recorrente_com_ingrediente_entra_no_deficit_junto_com_o_cardapio(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $leite = $this->ingrediente($casa, 'Leite', 'ml');
        app(\App\Modules\Cardapio\Services\EstoqueService::class)->adicionar($casa, $leite, 500, 'ml');
        $receita = $this->receita($casa, 'Mingau', [[$leite, 300]]);
        $plano->refeicoes()->update(['receita_id' => $receita->id]);

        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['ingrediente_id' => $leite->id, 'quantidade' => 2, 'unidade' => 'l'])->assertRedirect('/cardapio/recorrentes');
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");

        $item = $casa->listasCompras()->first()->itens()->first();
        $this->assertSame(2300.0, $item->quantidade_necessaria); // 2 L recorrentes + 300 ml da receita
        $this->assertSame(1800.0, $item->quantidade_comprar);    // menos os 500 ml em estoque
    }

    public function test_recorrente_livre_entra_uma_vez_e_pausado_e_ignorado(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['descricao' => 'Papel higiênico']);
        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['descricao' => 'Detergente']);
        $detergente = $casa->produtosRecorrentes()->where('descricao', 'Detergente')->first();
        $this->actingAs($usuario)->post("/cardapio/recorrentes/{$detergente->id}/alternar");

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $lista = $casa->listasCompras()->first();
        $this->assertSame(['Papel higiênico'], $lista->itens()->pluck('descricao')->all());

        $item = $lista->itens()->first();
        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens/{$item->id}/comprado");
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras"); // recalcula
        $this->assertSame(1, $lista->itens()->count()); // não duplica depois de comprado
    }

    public function test_lista_avulsa_de_recorrentes_e_validacoes(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $arroz = $this->ingrediente($casa, 'Arroz');
        $this->actingAs($usuario)->post('/cardapio/recorrentes', [])->assertSessionHasErrors('descricao');
        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['ingrediente_id' => $arroz->id])->assertSessionHasErrors('quantidade');
        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['ingrediente_id' => $arroz->id, 'quantidade' => 1, 'unidade' => 'l'])->assertSessionHasErrors('quantidade'); // unidade incompatível
        $this->actingAs($usuario)->post('/cardapio/recorrentes', ['ingrediente_id' => $arroz->id, 'quantidade' => 5, 'unidade' => 'kg']);

        $this->actingAs($usuario)->post('/cardapio/compras/recorrentes')->assertRedirect();
        $lista = $casa->listasCompras()->where('titulo', 'Compras recorrentes')->firstOrFail();
        $this->assertSame(5000.0, $lista->itens()->first()->quantidade_comprar);

        $this->actingAs($usuario)->post('/cardapio/compras/recorrentes'); // reaproveita a lista aberta
        $this->assertSame(1, $casa->listasCompras()->where('titulo', 'Compras recorrentes')->count());
        $this->actingAs($usuario)->get('/cardapio/recorrentes')->assertOk()->assertSee('Arroz');
    }

    public function test_recorrente_de_outra_casa_e_inacessivel(): void
    {
        [$usuario] = $this->casaComRotina();
        $outra = $this->casaCom(User::factory()->create());
        $produto = $outra->produtosRecorrentes()->create(['descricao' => 'Alheio']);

        $this->actingAs($usuario)->post("/cardapio/recorrentes/{$produto->id}/alternar")->assertNotFound();
        $this->actingAs($usuario)->delete("/cardapio/recorrentes/{$produto->id}")->assertNotFound();
    }

    public function test_mover_item_para_a_proxima_compra(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $lista = $casa->listasCompras()->create(['titulo' => 'Atual']);
        $item = $lista->itens()->create(['descricao' => 'Chocolate', 'manual' => true]);

        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens/{$item->id}/mover")->assertSessionHas('status');

        $proxima = $casa->listasCompras()->where('titulo', 'Próxima compra')->firstOrFail();
        $this->assertSame($proxima->id, $item->fresh()->lista_id);
        $this->assertTrue($item->fresh()->manual);

        // Segunda vez: já existe outra lista aberta, usa essa.
        $outroItem = $lista->itens()->create(['descricao' => 'Café']);
        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens/{$outroItem->id}/mover");
        $this->assertSame(1, $casa->listasCompras()->where('titulo', 'Próxima compra')->count());
        $this->assertSame(2, $proxima->itens()->count());

        $intruso = $this->casaCom(User::factory()->create());
        $alheio = $intruso->listasCompras()->create([])->itens()->create(['descricao' => 'X']);
        $this->actingAs($usuario)->post("/cardapio/compras/{$lista->id}/itens/{$alheio->id}/mover")->assertNotFound();
    }

    // ---- F8: lancheira ----

    /**
     * @return array{0: User, 1: Casa, 2: \App\Modules\Cardapio\Models\PlanoSemanal, 3: array<int, \App\Modules\Cardapio\Models\Receita>}
     */
    private function semanaComLancheira(int $receitas = 6, int $dias = 3): array
    {
        $usuario = User::factory()->create();
        $casa = $this->casaCom($usuario);
        $filho = $casa->pessoas()->create(['nome' => 'Filho', 'tipo' => 'crianca']);
        foreach (range(1, $dias) as $dia) {
            $casa->regrasRotina()->create(['pessoa_id' => $filho->id, 'dia_semana' => $dia, 'tipo_refeicao' => 'lancheira']);
        }
        $lista = [];
        foreach (range(1, $receitas) as $n) {
            $lista[] = $this->receita($casa, "Item {$n}", [[$this->ingrediente($casa, "Ing {$n}"), 100]], ['refeicoes_compativeis' => ['lancheira']]);
        }

        return [$usuario, $casa, $this->planejador()->criarSemana($casa, '2026-10-12'), $lista];
    }

    public function test_lancheira_recebe_varios_itens_sem_repetir_em_dias_seguidos(): void
    {
        [$usuario, $casa, $plano] = $this->semanaComLancheira();

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");

        $dias = $plano->refeicoes()->with('itens')->orderBy('data')->get();
        $this->assertCount(3, $dias);
        $conjuntos = [];
        foreach ($dias as $refeicao) {
            $ids = array_merge([$refeicao->receita_id], $refeicao->itens->pluck('receita_id')->all());
            $this->assertCount(3, array_unique($ids)); // principal + 2 itens, distintos
            $conjuntos[] = $ids;
        }
        $this->assertSame([], array_intersect($conjuntos[0], $conjuntos[1]));
        $this->assertSame([], array_intersect($conjuntos[1], $conjuntos[2]));
    }

    public function test_lancheira_com_poucas_receitas_completa_o_que_der(): void
    {
        [$usuario, , $plano] = $this->semanaComLancheira(receitas: 4, dias: 2);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");

        $dias = $plano->refeicoes()->with('itens')->orderBy('data')->get();
        $this->assertSame(2, $dias[0]->itens->count()); // dia 1: principal + 2 itens = 3 receitas
        $this->assertNotNull($dias[1]->receita_id);     // dia 2: só sobrou 1 receita livre (as outras são do dia 1)
        $this->assertSame(0, $dias[1]->itens->count());
        $this->assertSame([], array_intersect(
            array_merge([$dias[0]->receita_id], $dias[0]->itens->pluck('receita_id')->all()),
            [$dias[1]->receita_id]
        ));
    }

    public function test_itens_da_lancheira_entram_nas_compras_e_no_consumo(): void
    {
        [$usuario, $casa, $plano, $receitas] = $this->semanaComLancheira(receitas: 3, dias: 1);
        $refeicao = $plano->refeicoes()->first();
        $refeicao->update(['receita_id' => $receitas[0]->id]);
        $this->planejador()->definirItens($refeicao->fresh(), [$receitas[1]->id, $receitas[2]->id]);

        $this->assertSame(2, $refeicao->itens()->count());
        $this->assertCount(3, $refeicao->fresh()->load('receita.ingredientes', 'itens.receita.ingredientes')->necessidades());

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/compras");
        $this->assertSame(3, $casa->listasCompras()->first()->itens()->count());

        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        foreach ($casa->ingredientes()->get() as $ing) {
            $estoque->adicionar($casa, $ing, 1, 'kg');
        }
        $this->planejador()->consumir($refeicao->fresh());
        $this->assertSame(2700.0, (float) $casa->itensEstoque()->sum('quantidade')); // 3 kg − 3 × 100 g (1 pessoa)
    }

    public function test_definir_itens_adapta_e_avisa_sobre_restricoes(): void
    {
        [$usuario, $casa, $plano, $receitas] = $this->semanaComLancheira(receitas: 2, dias: 1);
        $filho = $casa->pessoas()->where('nome', 'Filho')->first();
        $refeicao = $plano->refeicoes()->first();
        $ing = $receitas[1]->ingredientes->first()->ingrediente_id;
        $filho->restricoes()->create(['tipo' => 'alergia', 'ingrediente_id' => $ing]);

        $avisos = $this->planejador()->definirItens($refeicao, [$receitas[1]->id]);

        $this->assertNotEmpty($avisos);
        $this->assertSame(1, $refeicao->itens()->count()); // escolha manual fica, com aviso

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", ['itens' => [$refeicao->id => [0]]]);
        $this->assertSame(0, $refeicao->itens()->count());
        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}")->assertOk()->assertSee('Itens da lancheira');
    }

    public function test_configurar_itens_da_lancheira_e_refazer_limpa_os_itens(): void
    {
        [$usuario, $casa, $plano] = $this->semanaComLancheira(dias: 1);
        $this->actingAs($usuario)->post('/cardapio/rotina/refeicoes', ['refeicoes' => ['lancheira', 'almoco'], 'lancheira_itens' => 2]);
        $this->assertSame(2, $casa->fresh()->lancheira_itens);
        $this->actingAs($usuario)->post('/cardapio/rotina/refeicoes', ['refeicoes' => ['lancheira'], 'lancheira_itens' => 9])->assertSessionHasErrors('lancheira_itens');

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar");
        $refeicao = $plano->refeicoes()->first();
        $this->assertSame(1, $refeicao->itens()->count()); // principal + 1

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/gerar");
        $this->assertSame(1, $refeicao->fresh()->itens()->count()); // refez e remontou, sem acumular
    }

    // ---- F8: aprendizado ----

    public function test_trocar_sugestao_conta_como_rejeicao_e_a_nova_como_escolha(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $a = $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $b = $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $refeicao = $plano->refeicoes()->first();
        $this->planejador()->gerar($plano, collect([$refeicao]));
        $sugerida = $refeicao->fresh()->receita_id;
        $outra = $sugerida === $a->id ? $b : $a;
        $this->assertSame('sugerida', $refeicao->fresh()->origem);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/escolher", ['receita_id' => $outra->id]);

        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($sugerida)->rejeicoes);
        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($outra->id)->escolhas);
        $this->assertSame('manual', $refeicao->fresh()->origem);

        // Trocar uma escolha manual não é rejeição; repetir a mesma receita não conta de novo.
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/escolher", ['receita_id' => $sugerida]);
        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($sugerida)->rejeicoes);
        $this->assertSame(0, \App\Modules\Cardapio\Models\Receita::find($outra->id)->rejeicoes);
        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($sugerida)->escolhas);
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}", ['receitas' => [$refeicao->id => $sugerida]]);
        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($sugerida)->escolhas); // mesma receita: não conta de novo
    }

    public function test_pedir_outra_para_uma_refeicao_penaliza_a_sugestao_anterior(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $refeicao = $plano->refeicoes()->first();
        $this->planejador()->gerar($plano, collect([$refeicao]));
        $primeira = $refeicao->fresh()->receita_id;

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/refeicoes/{$refeicao->id}/gerar");

        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($primeira)->rejeicoes);
        $this->assertNotSame($primeira, $refeicao->fresh()->receita_id);

        // Refazer o dia/semana inteiros é outra coisa: não conta como rejeição.
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar", ['tudo' => 1]);
        $this->assertSame(1, \App\Modules\Cardapio\Models\Receita::find($primeira)->rejeicoes);
    }

    public function test_aprendizado_muda_a_pontuacao_e_aparece_na_justificativa(): void
    {
        [, $casa, $plano] = $this->semanaSimples();
        $comum = $this->receita($casa, 'Comum', [[$this->ingrediente($casa, 'C'), 100]]);
        $querida = $this->receita($casa, 'Querida', [[$this->ingrediente($casa, 'Q'), 100]], ['escolhas' => 6]);
        $rejeitada = $this->receita($casa, 'Rejeitada', [[$this->ingrediente($casa, 'R'), 100]], ['rejeicoes' => 4]);

        $base = $this->avaliar($comum, $plano);
        $a = $this->avaliar($querida, $plano);
        $b = $this->avaliar($rejeitada, $plano);

        $this->assertGreaterThan($base['score'], $a['score']);
        $this->assertLessThan($base['score'], $b['score']);
        $this->assertStringContainsString('costumam escolher', implode(' ', $a['motivos']));
        $this->assertStringContainsString('costumam trocar', implode(' ', $b['avisos']));
    }

    // ---- F8: otimização global ----

    public function test_otimizacao_corrige_a_miopia_da_escolha_gulosa(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples();
        $eu = $casa->pessoas()->first();
        $casa->regrasRotina()->create(['pessoa_id' => $eu->id, 'dia_semana' => 1, 'tipo_refeicao' => 'jantar']);
        $plano = $this->planejador()->criarSemana($casa, '2026-10-12'); // agora com almoço e jantar de segunda
        $z = $this->ingrediente($casa, 'Z');
        $w = $this->ingrediente($casa, 'W');
        $estoque = app(\App\Modules\Cardapio\Services\EstoqueService::class);
        $estoque->adicionar($casa, $z, 500, 'g');
        $estoque->adicionar($casa, $w, 500, 'g');
        $a = $this->receita($casa, 'Só Z', [[$z, 500]], ['refeicoes_compativeis' => ['almoco']]);
        $b = $this->receita($casa, 'Z e W', [[$z, 500], [$w, 500]], ['refeicoes_compativeis' => ['almoco', 'jantar'], 'favorita' => true]);
        $c = $this->receita($casa, 'Só W', [[$w, 500]], ['refeicoes_compativeis' => ['jantar']]);

        // Gulosa (alvo explícito, sem otimizar): almoço escolhe a favorita e esgota Z e W.
        $this->planejador()->gerar($plano, $plano->refeicoes()->get());
        $almoco = $plano->refeicoes()->where('tipo_refeicao', 'almoco')->first();
        $jantar = $plano->refeicoes()->where('tipo_refeicao', 'jantar')->first();
        $this->assertSame($b->id, $almoco->receita_id);

        $r = $this->planejador()->otimizar($plano);

        $this->assertGreaterThan(0, $r['melhorias']);
        $this->assertGreaterThan($r['antes'], $r['depois']);
        $this->assertSame($a->id, $almoco->fresh()->receita_id);
        $this->assertSame($c->id, $jantar->fresh()->receita_id);
    }

    public function test_otimizacao_respeita_o_limite_de_tempo(): void
    {
        [, $casa, $plano] = $this->semanaSimples([1, 2]);
        $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);
        $this->planejador()->gerar($plano, $plano->refeicoes()->get());
        $antes = $plano->refeicoes()->orderBy('data')->pluck('receita_id')->all();

        $r = $this->planejador()->otimizar($plano, 3, 0.0); // sem tempo: não troca nada

        $this->assertSame(0, $r['melhorias']);
        $this->assertSame($antes, $plano->refeicoes()->orderBy('data')->pluck('receita_id')->all());
    }

    public function test_painel_mostra_a_agenda_dos_proximos_dias(): void
    {
        [$usuario, $casa] = $this->casaComRotina();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-12 09:00'));
        $casa->eventos()->create(['data' => '2026-10-14', 'tipo' => 'convidados', 'titulo' => 'Almoço com os pais', 'pessoas_extras' => 2]);
        $casa->eventos()->create(['data' => '2026-11-30', 'tipo' => 'fora', 'titulo' => 'Muito longe']);
        $eu = $casa->pessoas()->first();
        $casa->ausencias()->create(['pessoa_id' => $eu->id, 'inicio' => '2026-10-13', 'fim' => '2026-10-15', 'motivo' => 'viagem']);

        $this->actingAs($usuario)->get('/cardapio')->assertOk()
            ->assertSee('Almoço com os pais')->assertSee('viagem')->assertDontSee('Muito longe');
    }

    public function test_botao_otimizar_e_gerar_semana_inteira_mostram_resultado(): void
    {
        [$usuario, $casa, $plano] = $this->semanaSimples([1, 2]);
        $this->receita($casa, 'Receita A', [[$this->ingrediente($casa, 'A'), 100]]);
        $this->receita($casa, 'Receita B', [[$this->ingrediente($casa, 'B'), 100]]);

        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/gerar")->assertSessionHas('status');
        $this->actingAs($usuario)->post("/cardapio/planejamento/{$plano->id}/otimizar")->assertSessionHas('status');
        $this->actingAs($usuario)->get("/cardapio/planejamento/{$plano->id}")->assertOk()->assertSee('Pontuação da semana')->assertSee('Otimizar semana');

        $intruso = User::factory()->create();
        $this->casaCom($intruso);
        $this->actingAs($intruso)->post("/cardapio/planejamento/{$plano->id}/otimizar")->assertNotFound();
        $this->actingAs($intruso)->post("/cardapio/planejamento/{$plano->id}/sincronizar")->assertNotFound();
    }
}

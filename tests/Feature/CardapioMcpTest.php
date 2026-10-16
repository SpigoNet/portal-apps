<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Cardapio\Http\Middleware\AutenticarMcp;
use App\Modules\Cardapio\Models\Casa;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CardapioMcpTest extends TestCase
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
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'app/Modules/Cardapio/database/migrations']);

        $this->travelTo(Carbon::parse('2026-10-14 10:00'));

        $this->usuario = User::factory()->create();
        $this->casa = Casa::create(['nome' => 'Casa teste']);
        $this->casa->pessoas()->create(['user_id' => $this->usuario->id, 'nome' => 'Gustavo']);
    }

    /** @return array<string, string> */
    private function cabecalhos(?User $u = null): array
    {
        $u ??= $this->usuario;

        return ['X-User-ID' => (string) $u->id, 'X-Token' => AutenticarMcp::tokenDe($u)];
    }

    private function rpc(string $metodo, array $params = [], ?array $headers = null)
    {
        return $this->postJson('/cardapio/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $metodo, 'params' => $params], $headers ?? $this->cabecalhos());
    }

    /** @return array<string, mixed> */
    private function ferramenta(string $nome, array $args = []): array
    {
        $r = $this->rpc('tools/call', ['name' => $nome, 'arguments' => $args])->assertOk();

        return ['erro' => $r->json('result.isError'), 'dados' => json_decode($r->json('result.content.0.text'), true)];
    }

    public function test_exige_credenciais_validas(): void
    {
        $this->postJson('/cardapio/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
        $this->rpc('ping', [], ['X-User-ID' => (string) $this->usuario->id, 'X-Token' => 'errado'])->assertStatus(401);
    }

    public function test_aceita_bearer_e_rejeita_usuario_sem_casa(): void
    {
        $token = AutenticarMcp::tokenDe($this->usuario);
        $this->rpc('ping', [], ['Authorization' => "Bearer {$this->usuario->id}:{$token}"])->assertOk()->assertJsonPath('jsonrpc', '2.0');

        $sem = User::factory()->create();
        $this->rpc('ping', [], $this->cabecalhos($sem))->assertStatus(403);
    }

    public function test_initialize_e_lista_de_ferramentas(): void
    {
        $this->rpc('initialize', ['protocolVersion' => '2025-03-26'])
            ->assertOk()->assertJsonPath('result.protocolVersion', '2025-03-26')->assertJsonPath('result.serverInfo.name', 'Cardapio MCP');

        $nomes = array_column($this->rpc('tools/list')->json('result.tools'), 'name');
        $this->assertContains('adicionar_estoque', $nomes);
        $this->assertContains('consultar_estoque', $nomes);
    }

    public function test_notificacao_nao_tem_resposta_e_get_nao_e_suportado(): void
    {
        $this->postJson('/cardapio/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $this->cabecalhos())->assertStatus(202);
        $this->getJson('/cardapio/mcp')->assertStatus(405);
    }

    public function test_adiciona_e_consulta_estoque_e_nao_deixa_ficar_negativo(): void
    {
        $r = $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Frango', 'quantidade' => 2, 'unidade' => 'kg', 'local' => 'geladeira', 'validade_dias' => 3]);
        $this->assertFalse($r['erro']);
        $this->assertTrue($r['dados']['ok']);

        $c = $this->ferramenta('consultar_estoque', ['busca' => 'fra']);
        $this->assertSame('Frango', $c['dados']['itens'][0]['ingrediente']);

        $v = $this->ferramenta('estoque_vencendo');
        $this->assertSame('Frango', $v['dados']['itens'][0]['ingrediente']);

        $b = $this->ferramenta('baixar_estoque', ['ingrediente' => 'Frango', 'quantidade' => 50, 'unidade' => 'kg']);
        $this->assertFalse($b['erro']);
        $this->assertStringContainsString('o estoque zerou', $b['dados']['texto']);
        $this->assertSame(0.0, (float) $this->casa->itensEstoque()->sum('quantidade'));
    }

    public function test_entrada_invalida_vira_erro_da_ferramenta_e_nao_grava(): void
    {
        $r = $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Arroz', 'quantidade' => -1, 'unidade' => 'kg']);
        $this->assertTrue($r['erro']);
        $this->assertDatabaseCount('cardapio_itens_estoque', 0);

        $this->rpc('tools/call', ['name' => 'apagar_tudo'])->assertJsonPath('error.code', -32602);
    }

    public function test_compras_e_cardapio_vazio(): void
    {
        $this->assertTrue($this->ferramenta('adicionar_compra', ['descricao' => 'Chocolate'])['dados']['ok']);
        $itens = $this->ferramenta('ver_compras')['dados']['listas'][0]['itens'];
        $this->assertSame('Chocolate', $itens[0]['item']);

        $this->assertFalse($this->ferramenta('ver_cardapio')['dados']['planejada']);
        $this->assertSame('Gustavo', $this->ferramenta('listar_pessoas')['dados']['pessoas'][0]['nome']);
    }

    public function test_nao_enxerga_dados_de_outra_casa(): void
    {
        $outro = User::factory()->create();
        $outra = Casa::create(['nome' => 'Outra']);
        $outra->pessoas()->create(['user_id' => $outro->id, 'nome' => 'Fulano']);

        $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Frango', 'quantidade' => 1, 'unidade' => 'kg']);

        $r = $this->postJson('/cardapio/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'consultar_estoque', 'arguments' => []]], $this->cabecalhos($outro));
        $this->assertSame(0, json_decode($r->json('result.content.0.text'), true)['total_ingredientes']);
    }

    public function test_credenciais_na_url_funcionam_e_token_errado_nao(): void
    {
        $token = AutenticarMcp::tokenDe($this->usuario);
        $corpo = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

        $this->postJson("/cardapio/mcp/{$this->usuario->id}/{$token}", $corpo)->assertOk()->assertJsonPath('jsonrpc', '2.0');
        $this->postJson("/cardapio/mcp/{$this->usuario->id}/errado", $corpo)->assertStatus(401);
    }

    public function test_pagina_de_conexao_mostra_a_url_com_token(): void
    {
        $this->get('/cardapio/conexao-mcp')->assertRedirect('/login');

        $token = AutenticarMcp::tokenDe($this->usuario);
        $this->actingAs($this->usuario)->get('/cardapio/conexao-mcp')
            ->assertOk()->assertSee("/cardapio/mcp/{$this->usuario->id}/{$token}", false)->assertSee('adicionar_estoque')->assertSee('Prompt de contexto sugerido');
    }

    public function test_detalha_e_altera_receita(): void
    {
        $this->ferramenta('criar_receita', [
            'nome' => 'Panqueca', 'porcoes' => 4, 'refeicoes' => ['almoco'],
            'ingredientes' => [['ingrediente' => 'Farinha', 'quantidade' => 200, 'unidade' => 'g'], ['ingrediente' => 'Ovos', 'quantidade' => 2, 'unidade' => 'un']],
        ]);

        $this->assertSame('Panqueca', $this->ferramenta('listar_receitas', ['busca' => 'panq'])['dados']['receitas'][0]['nome']);

        $r = $this->ferramenta('alterar_receita', ['receita' => 'Panqueca', 'novo_nome' => 'Panqueca doce', 'porcoes' => 6, 'dificuldade' => 'facil']);
        $this->assertFalse($r['erro']);

        $d = $this->ferramenta('detalhar_receita', ['nome' => 'Panqueca doce'])['dados'];
        $this->assertSame(6, $d['porcoes']);
        $this->assertCount(2, $d['ingredientes']); // intocados

        $this->ferramenta('alterar_receita', ['receita' => 'Panqueca doce', 'ingredientes' => [['ingrediente' => 'Leite', 'quantidade' => 300, 'unidade' => 'ml']]]);
        $d = $this->ferramenta('detalhar_receita', ['nome' => 'Panqueca doce'])['dados'];
        $this->assertSame(['Leite'], array_column($d['ingredientes'], 'ingrediente'));
    }

    public function test_alterar_receita_valida_entrada(): void
    {
        $this->assertTrue($this->ferramenta('alterar_receita', ['receita' => 'Inexistente', 'porcoes' => 2])['erro']);

        $this->ferramenta('criar_receita', ['nome' => 'Sopa', 'ingredientes' => [['ingrediente' => 'Água', 'quantidade' => 1, 'unidade' => 'l']]]);
        $this->assertTrue($this->ferramenta('alterar_receita', ['receita' => 'Sopa'])['erro']);
        $this->assertTrue($this->ferramenta('alterar_receita', ['receita' => 'Sopa', 'dificuldade' => 'impossivel'])['erro']);
    }

    public function test_medidas_caseiras_valem_para_ingredientes_em_gramas_e_ml(): void
    {
        $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Açúcar', 'quantidade' => 1, 'unidade' => 'kg']);
        $r = $this->ferramenta('baixar_estoque', ['ingrediente' => 'Açúcar', 'quantidade' => 2, 'unidade' => 'colheres de sopa']);
        $this->assertFalse($r['erro']);
        $this->assertSame(970.0, (float) $this->casa->itensEstoque()->sum('quantidade'));

        $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Leite', 'quantidade' => 1, 'unidade' => 'l']);
        $this->assertFalse($this->ferramenta('baixar_estoque', ['ingrediente' => 'Leite', 'quantidade' => 1, 'unidade' => 'xícara'])['erro']);

        $this->ferramenta('adicionar_estoque', ['ingrediente' => 'Alho', 'quantidade' => 2, 'unidade' => 'un']);
        $this->assertTrue($this->ferramenta('baixar_estoque', ['ingrediente' => 'Alho', 'quantidade' => 1, 'unidade' => 'colher de chá'])['erro']);
        $this->assertFalse($this->ferramenta('baixar_estoque', ['ingrediente' => 'Alho', 'quantidade' => 1, 'unidade' => 'dente'])['erro']);
    }

    public function test_menu_do_cardapio_tem_icone_em_todos_os_itens(): void
    {
        $html = $this->actingAs($this->usuario)->get('/cardapio/conexao-mcp')->assertOk()->getContent();
        $lateral = substr($html, strpos($html, 'class="cd-lateral"'), 8000);
        preg_match_all('/<a href="[^"]+" class="cd-nav-i[^"]*">(<svg.*?<\/svg>)[^<]+<\/a>/s', $lateral, $m);

        $this->assertGreaterThanOrEqual(14, count($m[1]));
    }
}

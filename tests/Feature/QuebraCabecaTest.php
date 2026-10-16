<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\QuebraCabeca\Models\Grupo;
use App\Modules\QuebraCabeca\Models\Imagem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuebraCabecaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A cadeia completa de migrations não roda em sqlite; migra só o necessário.
        $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        foreach ([
            '2025_10_05_035231_create_packages_table.php',
            '2025_10_05_035312_create_portal_apps_table.php',
            '2026_01_28_144103_wpa.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_193624_create_metricas_acessos_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/QuebraCabeca/database/migrations/2026_10_12_130001_create_quebra_cabeca_imagens_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/QuebraCabeca/database/migrations/2026_10_12_140000_create_quebra_cabeca_grupos_table.php']);
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/quebra-cabeca')->assertRedirect('/login');
    }

    public function test_usuario_logado_ve_o_jogo(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/quebra-cabeca')
            ->assertOk()
            ->assertSee('quebraCabeca(', false)
            ->assertSee('Fazendinha', false);
    }

    public function test_usuario_envia_e_remove_imagem(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/quebra-cabeca/imagens', ['imagem' => UploadedFile::fake()->image('foto.jpg', 400, 300)])
            ->assertRedirect('/quebra-cabeca');

        $imagem = Imagem::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('foto', $imagem->nome);
        Storage::disk('public')->assertExists($imagem->caminho);

        $this->actingAs($user)->get("/quebra-cabeca/imagens/{$imagem->id}")->assertOk();

        $this->actingAs($user)->delete("/quebra-cabeca/imagens/{$imagem->id}")->assertRedirect('/quebra-cabeca');
        Storage::disk('public')->assertMissing($imagem->caminho);
        $this->assertDatabaseMissing('quebra_cabeca_imagens', ['id' => $imagem->id]);
    }

    public function test_imagem_de_outro_usuario_e_negada(): void
    {
        Storage::fake('public');
        $dono = User::factory()->create();
        $imagem = Imagem::create(['user_id' => $dono->id, 'nome' => 'x', 'caminho' => 'quebra-cabeca/x.jpg']);

        $this->actingAs(User::factory()->create())
            ->get("/quebra-cabeca/imagens/{$imagem->id}")
            ->assertForbidden();
    }

    public function test_orientacao_e_detectada_no_envio(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/quebra-cabeca/imagens', ['imagem' => UploadedFile::fake()->image('a.jpg', 300, 500)]);
        $this->actingAs($user)->post('/quebra-cabeca/imagens', ['imagem' => UploadedFile::fake()->image('b.jpg', 500, 300)]);

        $this->assertSame('retrato', Imagem::where('nome', 'a')->value('orientacao'));
        $this->assertSame('paisagem', Imagem::where('nome', 'b')->value('orientacao'));
    }

    public function test_grupos_agrupam_imagens_e_remocao_mantem_as_imagens(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/quebra-cabeca/grupos', ['nome' => 'Família'])->assertRedirect('/quebra-cabeca');
        $grupo = Grupo::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->post('/quebra-cabeca/imagens', [
            'imagem' => UploadedFile::fake()->image('f.jpg', 400, 300),
            'grupo_id' => $grupo->id,
        ]);
        $imagem = Imagem::firstOrFail();
        $this->assertSame($grupo->id, $imagem->grupo_id);

        $this->actingAs($user)->patch("/quebra-cabeca/imagens/{$imagem->id}", ['grupo_id' => null]);
        $this->assertNull($imagem->fresh()->grupo_id);

        $this->actingAs($user)->patch("/quebra-cabeca/imagens/{$imagem->id}", ['grupo_id' => $grupo->id]);
        $this->actingAs($user)->delete("/quebra-cabeca/grupos/{$grupo->id}")->assertRedirect('/quebra-cabeca');
        $this->assertDatabaseMissing('quebra_cabeca_grupos', ['id' => $grupo->id]);
        $this->assertNull($imagem->fresh()->grupo_id);
    }

    public function test_nao_usa_nem_remove_grupo_de_outro_usuario(): void
    {
        Storage::fake('public');
        $dono = User::factory()->create();
        $grupo = Grupo::create(['user_id' => $dono->id, 'nome' => 'Meu']);
        $intruso = User::factory()->create();

        $this->actingAs($intruso)
            ->post('/quebra-cabeca/imagens', ['imagem' => UploadedFile::fake()->image('x.jpg', 100, 100), 'grupo_id' => $grupo->id])
            ->assertSessionHasErrors('grupo_id');

        $this->actingAs($intruso)->delete("/quebra-cabeca/grupos/{$grupo->id}")->assertForbidden();
    }

    public function test_imagem_padrao_e_servida_e_arquivo_inexistente_da_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/quebra-cabeca/padrao/fazendinha.jpg')->assertOk();
        $this->actingAs($user)->get('/quebra-cabeca/padrao/nao-existe.jpg')->assertNotFound();
    }

    public function test_visitante_nao_acessa_imagem_padrao(): void
    {
        $this->get('/quebra-cabeca/padrao/fazendinha.jpg')->assertRedirect('/login');
    }
}

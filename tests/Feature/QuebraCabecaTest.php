<?php

namespace Tests\Feature;

use App\Models\User;
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
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_193624_create_metricas_acessos_table.php']);
        $this->artisan('migrate', ['--path' => 'app/Modules/QuebraCabeca/database/migrations/2026_10_12_130001_create_quebra_cabeca_imagens_table.php']);
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
            ->assertSee('Floresta', false);
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
}

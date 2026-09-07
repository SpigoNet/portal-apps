<?php

namespace Tests\Feature\Yomi;

use App\Models\User;
use App\Modules\Yomi\Models\Capitulo;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\ProgressoUsuario;
use App\Modules\Yomi\Models\UsuarioCapitulo;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class RemoveFromLibraryTest extends TestCase
{
    use RefreshYomiDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        $this->user = User::factory()->create();

        $this->withoutMiddleware(\App\Modules\Metricas\Http\Middleware\RegistrarAcesso::class);
    }

    public function test_remover_obra_limpa_progresso_e_catalogo_quando_ultimo_dono(): void
    {
        $manga = $this->createManga();
        $capitulo = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Capítulo 1',
        ]);

        ProgressoUsuario::create(['user_id' => $this->user->id, 'manga_id' => $manga->id, 'status' => 'lendo']);
        UsuarioCapitulo::create([
            'user_id' => $this->user->id,
            'manga_id' => $manga->id,
            'capitulo_id' => $capitulo->id,
            'read_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->post(route('yomi.mangas.remove', $manga))
            ->assertRedirect(route('yomi.library'))
            ->assertSessionHas('status', 'Obra removida da sua biblioteca.');

        $this->assertDatabaseMissing('yomi_progresso_usuario', ['user_id' => $this->user->id, 'manga_id' => $manga->id]);
        $this->assertDatabaseMissing('yomi_usuario_capitulos', ['user_id' => $this->user->id, 'manga_id' => $manga->id]);
        $this->assertDatabaseMissing('yomi_mangas', ['id' => $manga->id]);
        $this->assertDatabaseMissing('yomi_capitulos', ['id' => $capitulo->id]);
    }

    public function test_remover_obra_mantem_catalogo_quando_outro_usuario_tem_a_obra(): void
    {
        $manga = $this->createManga();

        $other = User::factory()->create();

        ProgressoUsuario::create(['user_id' => $this->user->id, 'manga_id' => $manga->id, 'status' => 'pretendo_ler']);
        ProgressoUsuario::create(['user_id' => $other->id, 'manga_id' => $manga->id, 'status' => 'lendo']);

        $this->actingAs($this->user)
            ->post(route('yomi.mangas.remove', $manga))
            ->assertRedirect(route('yomi.library'));

        $this->assertDatabaseHas('yomi_mangas', ['id' => $manga->id]);
        $this->assertDatabaseMissing('yomi_progresso_usuario', ['user_id' => $this->user->id, 'manga_id' => $manga->id]);
        $this->assertDatabaseHas('yomi_progresso_usuario', ['user_id' => $other->id, 'manga_id' => $manga->id]);
    }

    public function test_remover_obra_que_nao_esta_na_estante_nao_remove_catalogo(): void
    {
        $manga = $this->createManga();

        $this->actingAs($this->user)
            ->post(route('yomi.mangas.remove', $manga))
            ->assertRedirect(route('yomi.mangas.show', $manga));

        $this->assertDatabaseHas('yomi_mangas', ['id' => $manga->id]);
    }

    private function createManga(): Manga
    {
        $manga = Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);

        $manga->externalIds()->create(['provider' => 'jikan', 'external_id' => '123']);

        return $manga;
    }
}

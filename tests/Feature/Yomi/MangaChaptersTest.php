<?php

namespace Tests\Feature\Yomi;

use App\Models\User;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Models\Capitulo;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\UsuarioCapitulo;
use App\Modules\Yomi\Services\ProgressService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class MangaChaptersTest extends TestCase
{
    use RefreshYomiDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        Queue::fake();

        foreach (['jikan', 'anilist', 'kitsu', 'mal', 'mangadex'] as $provider) {
            config(["yomi.providers.{$provider}.throttle_ms" => 0]);
            config(["yomi.providers.{$provider}.retry.tries" => 1]);
        }

        config(['yomi.providers.mangadex.enabled' => true]);
        config(['yomi.providers.jikan.enabled' => true]);

        $this->user = User::factory()->create();

        $this->withoutMiddleware(\App\Modules\Metricas\Http\Middleware\RegistrarAcesso::class);
    }

    public function test_exibe_lista_de_capitulos_com_marcacao_de_lido_e_nao_lido(): void
    {
        $manga = Manga::create([
            'titulo' => 'Chainsaw Man',
            'status_publicacao' => 'publicando',
            'capitulos_conhecidos' => 3,
        ]);

        $cap1 = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Cachorro e Motosserra',
            'provider' => 'jikan',
        ]);

        $cap2 = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 2,
            'titulo' => 'O Lugar Onde Denji Mora',
            'provider' => 'jikan',
        ]);

        $cap3 = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 3,
            'titulo' => 'Chegada a Tóquio',
            'provider' => 'jikan',
        ]);

        // Marca apenas o capítulo 1 como lido
        app(ProgressService::class)->markChapterRead($this->user->id, $manga->id, $cap1->id);

        $response = $this->actingAs($this->user)
            ->get(route('yomi.mangas.show', $manga));

        $response->assertOk();
        $response->assertSee('Chainsaw Man');
        $response->assertSee('Capítulos (3)');
        $response->assertSee('Cachorro e Motosserra');
        $response->assertSee('O Lugar Onde Denji Mora');
        $response->assertSee('Chegada a Tóquio');
        $response->assertSee('✓ LIDO');
        $response->assertSee('NÃO LIDO');
        $response->assertSee('Atualizar capítulos da API');
    }

    public function test_toggle_capitulo_marca_e_desmarca_como_lido(): void
    {
        $manga = Manga::create([
            'titulo' => 'One Piece',
            'status_publicacao' => 'publicando',
            'capitulos_conhecidos' => 1,
        ]);

        $capitulo = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Romance Dawn',
            'provider' => 'jikan',
        ]);

        // 1. Marca como lido via toggle
        $response = $this->actingAs($this->user)
            ->post(route('yomi.mangas.chapters.toggle', [$manga, $capitulo]));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Capítulo 1 marcado como lido.');
        $this->assertTrue(UsuarioCapitulo::where('user_id', $this->user->id)->where('capitulo_id', $capitulo->id)->exists());

        // 2. Desmarca via toggle
        $response = $this->actingAs($this->user)
            ->post(route('yomi.mangas.chapters.toggle', [$manga, $capitulo]));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Capítulo 1 desmarcado como lido.');
        $this->assertFalse(UsuarioCapitulo::where('user_id', $this->user->id)->where('capitulo_id', $capitulo->id)->exists());
    }

    public function test_progresso_legado_e_registros_individuais_sao_combinados_na_lista(): void
    {
        $manga = Manga::create([
            'titulo' => 'Pluto',
            'status_publicacao' => 'completo',
            'capitulos_conhecidos' => 3,
        ]);

        $chapters = collect(range(1, 3))->map(fn (int $number): Capitulo => Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => $number,
            'titulo' => 'Capítulo '.$number,
            'provider' => 'jikan',
        ]));

        app(ProgressService::class)->updateProgress($this->user->id, $manga->id, 2);
        app(ProgressService::class)->markChapterRead($this->user->id, $manga->id, $chapters->get(2)->id);

        $response = $this->actingAs($this->user)->get(route('yomi.mangas.show', $manga));

        $response->assertOk();
        $response->assertSee('3 de 3 capítulos lidos (100%)');
        $this->assertSame(3, UsuarioCapitulo::where('user_id', $this->user->id)->where('manga_id', $manga->id)->count());
    }

    public function test_geracao_automatica_de_capitulos_quando_ausentes_na_rota_show(): void
    {
        $manga = Manga::create([
            'titulo' => 'Death Note',
            'status_publicacao' => 'completo',
            'capitulos_conhecidos' => 5,
        ]);

        $this->assertSame(0, $manga->capitulos()->count());

        $response = $this->actingAs($this->user)
            ->get(route('yomi.mangas.show', $manga));

        $response->assertOk();
        $this->assertSame(5, $manga->capitulos()->count());
        $response->assertSee('Capítulo 1');
        $response->assertSee('Capítulo 5');
    }

    public function test_sincronizacao_manual_de_capitulos_via_mangadex(): void
    {
        $manga = Manga::create([
            'titulo' => 'Frieren',
            'status_publicacao' => 'publicando',
            'capitulos_conhecidos' => 0,
        ]);

        $manga->externalIds()->create([
            'provider' => ProviderName::MangaDex->value,
            'external_id' => 'frieren-uuid',
        ]);

        Http::fake([
            'api.mangadex.org/manga/frieren-uuid/feed*' => Http::response([
                'data' => [
                    [
                        'id' => 'ch-1',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'O Fim da Jornada',
                            'publishAt' => '2020-04-28T00:00:00+00:00',
                        ],
                    ],
                    [
                        'id' => 'ch-2',
                        'attributes' => [
                            'chapter' => '2',
                            'title' => 'A Mentira do Sacerdote',
                            'publishAt' => '2020-05-12T00:00:00+00:00',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('yomi.mangas.sync-chapters', $manga));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame(2, $manga->capitulos()->count());
        $this->assertDatabaseHas('yomi_capitulos', [
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'O Fim da Jornada',
        ]);
        $this->assertDatabaseHas('yomi_capitulos', [
            'manga_id' => $manga->id,
            'numero' => 2,
            'titulo' => 'A Mentira do Sacerdote',
        ]);
    }

    public function test_sincronizacao_atualiza_metadados_de_capitulo_existente(): void
    {
        $manga = Manga::create([
            'titulo' => 'Frieren',
            'status_publicacao' => 'publicando',
        ]);
        $manga->externalIds()->create([
            'provider' => ProviderName::MangaDex->value,
            'external_id' => 'frieren-uuid',
        ]);
        $chapter = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Título provisório',
            'external_id' => 'old-id',
            'provider' => ProviderName::MangaDex->value,
        ]);

        Http::fake([
            'api.mangadex.org/manga/frieren-uuid/feed*' => Http::response([
                'data' => [[
                    'id' => 'chapter-uuid',
                    'attributes' => [
                        'chapter' => '1',
                        'title' => 'O Fim da Jornada',
                        'publishAt' => '2020-04-28T00:00:00+00:00',
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($this->user)->post(route('yomi.mangas.sync-chapters', $manga));

        $chapter->refresh();
        $this->assertSame('O Fim da Jornada', $chapter->titulo);
        $this->assertSame('chapter-uuid', $chapter->external_id);
        $this->assertSame('2020-04-28', $chapter->data_publicacao?->toDateString());
    }

    public function test_sincronizacao_manual_com_fallback_para_capitulos_conhecidos(): void
    {
        $manga = Manga::create([
            'titulo' => 'Monster',
            'status_publicacao' => 'completo',
            'capitulos_conhecidos' => 8,
        ]);

        $manga->externalIds()->create([
            'provider' => ProviderName::Jikan->value,
            'external_id' => '1',
        ]);

        Http::fake([
            'api.jikan.moe/*' => Http::response(['status' => 404, 'message' => 'Not found'], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('yomi.mangas.sync-chapters', $manga));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame(8, $manga->capitulos()->count());
        $this->assertDatabaseHas('yomi_capitulos', [
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Capítulo 1',
        ]);
        $this->assertDatabaseHas('yomi_capitulos', [
            'manga_id' => $manga->id,
            'numero' => 8,
            'titulo' => 'Capítulo 8',
        ]);
    }
}

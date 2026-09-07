<?php

namespace Tests\Feature\Yomi;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class MangaApiTest extends TestCase
{
    use RefreshYomiDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        Queue::fake();

        config(['yomi.providers.jikan.throttle_ms' => 0]);
        config(['yomi.providers.anilist.throttle_ms' => 0]);
        config(['yomi.providers.jikan.retry.tries' => 1]);
        config(['yomi.providers.anilist.retry.tries' => 1]);

        $this->user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/yomi/api/v1/health')
            ->assertOk()
            ->assertJsonPath('module', 'yomi')
            ->assertJsonPath('status', 'up');
    }

    public function test_api_requires_token_headers(): void
    {
        $this->getJson('/yomi/api/v1/mangas/populares')
            ->assertStatus(401);

        $this->getJson('/yomi/api/v1/mangas/populares', [
            'X-User-ID' => $this->user->id,
            'X-Token' => 'invalid-token',
        ])->assertStatus(401);
    }

    public function test_busca_retorna_lista_json(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [
                $this->jikanPayload(),
                $this->jikanPayload(['mal_id' => 124, 'title' => 'One Piece']),
            ]]),
        ]);

        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/busca?q=berserk')
            ->assertOk()
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('data.0.titles.romaji', 'Berserk')
            ->assertJsonPath('data.0.external_ids.jikan', '123')
            ->assertJsonPath('data.0.local', false)
            ->assertJsonPath('data.0.genres.0', 'Action');
    }

    public function test_busca_sem_consulta_e_rejeitada(): void
    {
        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/busca')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_populares_retorna_destaques(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [
                $this->jikanPayload(),
            ]]),
        ]);

        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/populares?limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.titles.romaji', 'Berserk')
            ->assertJsonPath('meta.limit', 5);
    }

    public function test_recentes_retorna_obras_recentes(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [
                $this->jikanPayload(['title' => 'Obra Nova']),
            ]]),
        ]);

        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/recentes')
            ->assertOk()
            ->assertJsonPath('data.0.titles.romaji', 'Obra Nova');
    }

    public function test_erro_502_quando_todos_provedores_falham(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 503),
            'graphql.anilist.co/*' => Http::response([], 503),
        ]);

        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/populares')
            ->assertStatus(502);
    }

    public function test_externo_busca_e_cadastra_obra(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => $this->jikanPayload()]),
        ]);

        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/externo/jikan/123')
            ->assertOk()
            ->assertJsonPath('data.local', true)
            ->assertJsonPath('data.external_ids.jikan', '123')
            ->assertJsonPath('meta.synced', true);
    }

    public function test_externo_com_provedor_invalido_retorna_422(): void
    {
        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/externo/desconhecido/123')
            ->assertStatus(422);
    }

    public function test_store_adiciona_obra_a_biblioteca(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => $this->jikanPayload()]),
        ]);

        $this->requestJson()
            ->postJson('/yomi/api/v1/mangas', [
                'provider' => 'jikan',
                'external_id' => '123',
            ])
            ->assertCreated()
            ->assertJsonPath('meta.created', true)
            ->assertJsonPath('data.external_ids.jikan', '123')
            ->assertJsonPath('data.cover_url', 'https://cdn.example.com/berserk.jpg')
            ->assertJsonPath('data.local', true);

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Berserk']);
        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'status' => 'pretendo_ler',
        ]);
    }

    public function test_store_obra_existente_marca_pretendo_ler(): void
    {
        $manga = \App\Modules\Yomi\Models\Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);
        $manga->externalIds()->create([
            'provider' => 'jikan',
            'external_id' => '123',
        ]);

        $this->requestJson()
            ->postJson('/yomi/api/v1/mangas', [
                'provider' => 'jikan',
                'external_id' => '123',
            ])
            ->assertOk()
            ->assertJsonPath('meta.created', false)
            ->assertJsonPath('data.id', $manga->id);

        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'manga_id' => $manga->id,
            'status' => 'pretendo_ler',
        ]);
    }

    public function test_show_retorna_obra_local(): void
    {
        $manga = \App\Modules\Yomi\Models\Manga::create([
            'titulo' => 'Berserk',
            'titulo_original' => 'ベルセルク',
            'status_publicacao' => 'completo',
            'tipo' => 'manga',
            'capitulos_conhecidos' => 364,
            'volumes_conhecidos' => 40,
            'nota_media' => 9.5,
            'sync_status' => 'sincronizado',
            'last_synced_at' => now(),
        ]);
        $manga->externalIds()->create([
            'provider' => 'jikan',
            'external_id' => '123',
        ]);

        $this->requestJson()
            ->getJson("/yomi/api/v1/mangas/{$manga->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $manga->id)
            ->assertJsonPath('data.local', true)
            ->assertJsonPath('data.titles.original', 'ベルセルク')
            ->assertJsonPath('data.external_ids.jikan', '123');
    }

    public function test_show_obra_inexistente_retorna_404(): void
    {
        $this->requestJson()
            ->getJson('/yomi/api/v1/mangas/99999')
            ->assertNotFound();
    }

    private function requestJson(): static
    {
        return $this->withHeaders([
            'Accept' => 'application/json',
            'X-User-ID' => (string) $this->user->id,
            'X-Token' => md5($this->user->email.$this->user->password),
        ]);
    }

    private function jikanPayload(array $overrides = []): array
    {
        return array_merge([
            'mal_id' => 123,
            'url' => 'https://myanimelist.net/manga/123',
            'title' => 'Berserk',
            'title_english' => 'Berserk (EN)',
            'title_japanese' => 'ベルセルク',
            'title_synonyms' => ['Kenpū Denki Berserk'],
            'type' => 'Manga',
            'chapters' => 364,
            'volumes' => 40,
            'status' => 'Finished',
            'published' => [
                'from' => '1989-01-01T00:00:00+00:00',
                'to' => '2021-09-10T00:00:00+00:00',
            ],
            'score' => 9.5,
            'synopsis' => 'Um espadachim das trevas.',
            'genres' => [
                ['mal_id' => 1, 'name' => 'Action'],
                ['mal_id' => 10, 'name' => 'Fantasy'],
            ],
            'authors' => [
                ['mal_id' => 789, 'type' => 'Story & Art', 'name' => 'Kentaro Miura', 'url' => 'https://example.com/miura'],
            ],
            'images' => [
                'jpg' => ['large_image_url' => 'https://cdn.example.com/berserk.jpg'],
            ],
        ], $overrides);
    }
}

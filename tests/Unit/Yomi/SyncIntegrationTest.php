<?php

namespace Tests\Unit\Yomi;

use App\Modules\Yomi\Exceptions\YomiSyncException;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Services\MangaSyncService;
use App\Modules\Yomi\Services\SyncManager;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class SyncIntegrationTest extends TestCase
{
    use RefreshYomiDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        Queue::fake();

        config(['yomi.providers.jikan.throttle_ms' => 0]);
        config(['yomi.providers.anilist.throttle_ms' => 0]);
        config(['yomi.providers.jikan.retry.tries' => 1]);
        config(['yomi.providers.anilist.retry.tries' => 1]);

        $this->syncManager = app(SyncManager::class);
        $this->mangaSyncService = app(MangaSyncService::class);
    }

    public function test_obra_inexistente_criada_via_jikan(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => $this->jikanPayload()]),
        ]);

        $result = $this->syncManager->getOrSync(externalId: '123', provider: 'jikan');

        $this->assertFalse($result->fromCache);
        $this->assertTrue($result->synced);
        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Berserk']);
    }

    public function test_obra_local_fresca_retornada_sem_calls_externas(): void
    {
        $manga = $this->createManga(['last_synced_at' => now()]);

        Http::fake();
        Http::preventStrayRequests();

        $result = $this->syncManager->getOrSync($manga->id);

        $this->assertTrue($result->fromCache);
        Http::assertNothingSent();
    }

    public function test_obra_stale_pode_ser_atualizada(): void
    {
        $manga = $this->createManga(['last_synced_at' => now()->subDays(2), 'sync_status' => 'sincronizado']);
        $manga->externalIds()->createMany([
            ['provider' => 'jikan', 'external_id' => '123'],
        ]);

        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => $this->jikanPayload(['title' => 'Berserk (Atualizado)'])]),
        ]);

        $result = $this->syncManager->getOrSync($manga->id, dispatchInBackground: false);

        $this->assertSame('ベルセルク', $result->manga->titulo_original);
        $this->assertSame('sincronizado', $result->manga->sync_status);
        $this->assertTrue($result->manga->last_synced_at->gt(now()->subMinute()));
    }

    public function test_timeout_do_jikan_aciona_anilist(): void
    {
        Http::fake([
            'api.jikan.moe/*' => fn () => throw new ConnectException(
                'Connection timed out',
                new Request('GET', 'https://api.jikan.moe/v4/manga/123'),
            ),
            'graphql.anilist.co/*' => Http::response(['data' => ['Media' => $this->anilistPayload()]]),
        ]);

        $result = $this->syncManager->getOrSync(externalId: '123', provider: 'jikan');

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Fanged Bleach']);
        $this->assertDatabaseHas('yomi_manga_external_ids', ['provider' => 'anilist']);
    }

    public function test_rate_limit_do_jikan_aciona_anilist(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 429),
            'graphql.anilist.co/*' => Http::response(['data' => ['Media' => $this->anilistPayload()]]),
        ]);

        $result = $this->syncManager->getOrSync(externalId: '123', provider: 'jikan');

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Fanged Bleach']);
        $this->assertDatabaseHas('yomi_sync_logs', ['provider' => 'jikan', 'status' => 'falha', 'error_type' => 'rate_limited']);
    }

    public function test_sucesso_do_anilist_isolado(): void
    {
        Http::fake([
            'graphql.anilist.co/*' => Http::response(['data' => ['Media' => $this->anilistPayload()]]),
        ]);

        $result = $this->syncManager->getOrSync(externalId: '999', provider: 'anilist');

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Fanged Bleach']);
        $this->assertSame('anilist', $result->provider->value);
    }

    public function test_falha_de_ambos_nao_remove_dados_locais(): void
    {
        $manga = $this->createManga(['last_synced_at' => now()->subDays(2), 'sync_status' => 'sincronizado']);
        $manga->externalIds()->createMany([
            ['provider' => 'jikan', 'external_id' => '123'],
            ['provider' => 'anilist', 'external_id' => '999'],
        ]);

        Http::fake([
            'api.jikan.moe/*' => Http::response([], 500),
            'graphql.anilist.co/*' => Http::response([], 500),
        ]);

        $this->expectException(YomiSyncException::class);

        try {
            $this->mangaSyncService->sync($manga->id);
        } finally {
            $fresh = Manga::find($manga->id);

            $this->assertNotNull($fresh);
            $this->assertSame('Berserk', $fresh->titulo);
            $this->assertSame('falha', $fresh->sync_status);
            $this->assertDatabaseHas('yomi_sync_logs', ['provider' => 'jikan', 'status' => 'falha']);
            $this->assertDatabaseHas('yomi_sync_logs', ['provider' => 'anilist', 'status' => 'falha']);
        }
    }

    public function test_busca_utiliza_fallback(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 503),
            'graphql.anilist.co/*' => Http::response([
                'data' => [
                    'Page' => ['media' => [$this->anilistPayload()]],
                ],
            ]),
        ]);

        $results = $this->syncManager->search('berserk');

        $this->assertCount(1, $results);
        $this->assertSame('Fanged Bleach', $results[0]->title);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'busca', 'provider' => 'anilist', 'status' => 'sucesso']);
    }

    public function test_descobrir_populares_via_jikan(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [
                $this->jikanPayload(),
                $this->jikanPayload(['mal_id' => 124, 'title' => 'One Piece']),
            ]]),
        ]);

        $results = $this->syncManager->discover();

        $this->assertCount(2, $results);
        $this->assertSame('Berserk', $results[0]->title);
        $this->assertSame('jikan', $results[0]->externalIds[0]['provider']);
        $this->assertSame('123', $results[0]->externalIds[0]['external_id']);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'descoberta', 'provider' => 'jikan', 'status' => 'sucesso']);
    }

    public function test_descobrir_utiliza_fallback_para_anilist(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 503),
            'graphql.anilist.co/*' => Http::response([
                'data' => [
                    'Page' => ['media' => [$this->anilistPayload()]],
                ],
            ]),
        ]);

        $results = $this->syncManager->discover();

        $this->assertCount(1, $results);
        $this->assertSame('Fanged Bleach', $results[0]->title);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'descoberta', 'provider' => 'anilist', 'status' => 'sucesso']);
    }

    public function test_descobrir_falha_total_lanca_excecao(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 503),
            'graphql.anilist.co/*' => Http::response([], 503),
        ]);

        $this->expectException(YomiSyncException::class);

        $this->syncManager->discover();
    }

    public function test_prioridade_e_configuravel_e_prefere_anilist(): void
    {
        config(['yomi.provider_priority' => ['anilist', 'jikan']]);

        Http::fake([
            'api.jikan.moe/*' => Http::response([], 500),
            'graphql.anilist.co/*' => Http::response([
                'data' => [
                    'Page' => ['media' => [$this->anilistPayload()]],
                ],
            ]),
        ]);

        $results = $this->syncManager->search('berserk');

        $this->assertCount(1, $results);
        $this->assertSame('Fanged Bleach', $results[0]->title);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'busca', 'provider' => 'anilist', 'status' => 'sucesso']);
        $this->assertDatabaseMissing('yomi_sync_logs', ['operation' => 'busca', 'provider' => 'jikan']);
    }

    public function test_prioridade_invalida_na_config_cai_para_default(): void
    {
        config(['yomi.provider_priority' => ['desconhecido']]);

        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [$this->jikanPayload()]]),
            'graphql.anilist.co/*' => Http::response([], 500),
        ]);

        $results = $this->syncManager->search('berserk');

        $this->assertCount(1, $results);
        $this->assertSame('jikan', $results[0]->externalIds[0]['provider']);
    }

    public function test_recentes_via_jikan(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response(['data' => [
                $this->jikanPayload(),
                $this->jikanPayload(['mal_id' => 124, 'title' => 'One Piece', 'chapters' => 1100]),
            ]]),
        ]);

        $results = $this->syncManager->recent();

        $this->assertCount(2, $results);
        $this->assertSame('Berserk', $results[0]->title);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'descoberta', 'provider' => 'jikan', 'status' => 'sucesso']);
    }

    public function test_recentes_utiliza_fallback_para_anilist(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 504),
            'graphql.anilist.co/*' => Http::response([
                'data' => [
                    'Page' => ['media' => [$this->anilistPayload()]],
                ],
            ]),
        ]);

        $results = $this->syncManager->recent();

        $this->assertCount(1, $results);
        $this->assertSame('Fanged Bleach', $results[0]->title);
        $this->assertDatabaseHas('yomi_sync_logs', ['operation' => 'descoberta', 'provider' => 'anilist', 'status' => 'sucesso']);
    }

    public function test_recentes_falha_total_lanca_excecao(): void
    {
        Http::fake([
            'api.jikan.moe/*' => Http::response([], 503),
            'graphql.anilist.co/*' => Http::response([], 503),
        ]);

        $this->expectException(YomiSyncException::class);

        $this->syncManager->recent();
    }

    private function createManga(array $overrides = []): Manga
    {
        return Manga::create(array_merge([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ], $overrides));
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

    private function anilistPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 999,
            'idMal' => 123,
            'title' => [
                'romaji' => 'Fanged Bleach',
                'english' => 'Fanged Bleach (EN)',
                'native' => '牙をむく',
            ],
            'description' => '<p>Uma obra de fantasia sombria.</p>',
            'status' => 'FINISHED',
            'format' => 'MANGA',
            'chapters' => 364,
            'volumes' => 40,
            'startDate' => ['year' => 1989, 'month' => 1, 'day' => 1],
            'endDate' => ['year' => 2021, 'month' => 9, 'day' => 10],
            'genres' => ['Action', 'Fantasy'],
            'averageScore' => 95,
            'coverImage' => ['extraLarge' => 'https://cdn.example.com/fanged.jpg'],
            'staff' => ['edges' => []],
            'characters' => ['edges' => []],
        ], $overrides);
    }
}

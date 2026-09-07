<?php

namespace Tests\Unit\Yomi;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Normalizers\KitsuNormalizer;
use App\Modules\Yomi\Normalizers\MalNormalizer;
use App\Modules\Yomi\Normalizers\MangaDexNormalizer;
use App\Modules\Yomi\Providers\MalProvider;
use App\Modules\Yomi\Providers\ProviderResolver;
use App\Modules\Yomi\Services\YomiSettingsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class NewProvidersTest extends TestCase
{
    use RefreshYomiDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        foreach (['jikan', 'anilist', 'kitsu', 'mal', 'mangadex'] as $provider) {
            config(["yomi.providers.{$provider}.throttle_ms" => 0]);
            config(["yomi.providers.{$provider}.retry.tries" => 1]);
        }

        config(['yomi.providers.kitsu.enabled' => true]);
        config(['yomi.providers.mal.enabled' => true]);
        config(['yomi.providers.mangadex.enabled' => true]);
    }

    public function test_kitsu_normalizer_captures_metadata(): void
    {
        $manga = (new KitsuNormalizer)->normalizeManga($this->kitsuPayload(), $this->kitsuIncluded());

        $this->assertSame('Berserk', $manga->title);
        $this->assertSame('ベルセルク', $manga->originalTitle);
        $this->assertSame(376, $manga->chapters);
        $this->assertSame(42, $manga->volumes);
        $this->assertSame('publicando', $manga->status);
        $this->assertSame('https://example.com/poster.jpg', $manga->imageUrl);
        $this->assertSame(['Action', 'Fantasy'], $manga->genres);
        $this->assertSame(8.95, $manga->score);
        $this->assertSame([['provider' => 'kitsu', 'external_id' => '123']], $manga->externalIds);
        $this->assertSame('Kentaro Miura', $manga->creators[0]->name);
        $this->assertSame('Story & Art', $manga->creators[0]->role);
    }

    public function test_mal_normalizer_captures_metadata(): void
    {
        $manga = (new MalNormalizer)->normalizeManga($this->malPayload());

        $this->assertSame('Berserk', $manga->title);
        $this->assertSame('ベルセルク', $manga->originalTitle);
        $this->assertSame(376, $manga->chapters);
        $this->assertSame(42, $manga->volumes);
        $this->assertSame('completo', $manga->status);
        $this->assertSame(9.47, $manga->score);
        $this->assertSame(['Action', 'Drama'], $manga->genres);
        $this->assertSame([['provider' => 'mal', 'external_id' => '2']], $manga->externalIds);
        $this->assertSame('Kentaro Miura', $manga->creators[0]->name);
        $this->assertContains('ベルセルク', $manga->alternativeTitles);
    }

    public function test_mangadex_normalizer_captures_metadata_e_capa(): void
    {
        $manga = (new MangaDexNormalizer)->normalizeManga($this->mangadexPayload());

        $this->assertSame('Berserk', $manga->title);
        $this->assertSame(376, $manga->chapters);
        $this->assertSame(42, $manga->volumes);
        $this->assertSame('publicando', $manga->status);
        $this->assertSame(['Action', 'Drama'], $manga->genres);
        $this->assertSame('https://uploads.mangadex.org/covers/manga-1/cover.jpg.512.jpg', $manga->imageUrl);
        $this->assertSame('autor', $manga->creators[0]->role);
    }

    public function test_mangadex_normaliza_capitulos(): void
    {
        $chapters = (new MangaDexNormalizer)->normalizeChapters([
            [
                'id' => 'ch-final',
                'attributes' => ['chapter' => '376', 'title' => 'Final', 'publishAt' => '2021-09-10T00:00:00+00:00'],
            ],
        ]);

        $this->assertSame(376.0, $chapters[0]->number);
        $this->assertSame('Final', $chapters[0]->title);
        $this->assertSame('ch-final', $chapters[0]->externalId);
        $this->assertSame('2021-09-10', $chapters[0]->publishedAt);
    }

    public function test_mal_sem_client_id_retorna_nao_configurado_sem_requisicao(): void
    {
        $health = (new MalProvider(new MalNormalizer))->health();

        $this->assertSame('not_configured', $health['status']);
        Http::assertNothingSent();
    }

    public function test_mal_health_envia_x_mal_client_id(): void
    {
        app(YomiSettingsService::class)->save([
            'priority' => ['mal'],
            'providers' => [
                'mal' => ['enabled' => true, 'base_url' => null, 'credentials' => ['client_id' => 'abc123']],
            ],
        ]);

        Http::fake(['api.myanimelist.net/*' => Http::response(['id' => 1], 200)]);

        $health = app(ProviderResolver::class)->resolve('mal')->health();

        $this->assertSame('ok', $health['status']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-MAL-CLIENT-ID', 'abc123'));
    }

    public function test_anilist_health_envia_token_quando_configurado(): void
    {
        app(YomiSettingsService::class)->save([
            'priority' => ['anilist'],
            'providers' => [
                'anilist' => ['enabled' => true, 'base_url' => null, 'credentials' => ['token' => 'tok-123']],
            ],
        ]);

        Http::fake(['graphql.anilist.co/*' => Http::response(['data' => ['Page' => ['media' => []]]])]);

        $health = app(ProviderResolver::class)->resolve('anilist')->health();

        $this->assertSame('ok', $health['status']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer tok-123'));
    }

    public function test_kitsu_search_retorna_obras_normalizadas(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => [$this->kitsuPayload()],
                'included' => $this->kitsuIncluded(),
            ], 200),
        ]);

        $results = app(ProviderResolver::class)->resolve('kitsu')->search('Berserk');

        $this->assertCount(1, $results);
        $this->assertSame('Berserk', $results[0]->title);
        $this->assertSame(['Action', 'Fantasy'], $results[0]->genres);
        $this->assertSame(ProviderName::Kitsu->value, $results[0]->externalIds[0]['provider']);
    }

    public function test_health_de_kitsu_e_mangadex_ok(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response(['data' => []], 200),
            'api.mangadex.org/*' => Http::response(['result' => 'ok', 'data' => []], 200),
        ]);

        $this->assertSame('ok', app(ProviderResolver::class)->resolve('kitsu')->health()['status']);
        $this->assertSame('ok', app(ProviderResolver::class)->resolve('mangadex')->health()['status']);
    }

    private function kitsuPayload(): array
    {
        return [
            'id' => '123',
            'type' => 'manga',
            'attributes' => [
                'canonicalTitle' => 'Berserk',
                'titles' => ['en' => 'Berserk', 'en_jp' => 'ベルセルク'],
                'synopsis' => '<p>O Guts.</p>',
                'averageRating' => '89.5',
                'status' => 'current',
                'subtype' => 'manga',
                'chapterCount' => 376,
                'volumeCount' => 42,
                'startDate' => '1989-08-25',
                'endDate' => null,
                'ageRating' => 'R',
                'posterImage' => ['large' => 'https://example.com/poster.jpg'],
            ],
            'relationships' => [
                'genres' => ['data' => [
                    ['id' => '1', 'type' => 'genres'],
                    ['id' => '2', 'type' => 'genres'],
                ]],
                'staff' => ['data' => [
                    ['id' => '151', 'type' => 'mediaStaff'],
                ]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kitsuIncluded(): array
    {
        return [
            ['type' => 'genres', 'id' => '1', 'attributes' => ['name' => 'Action']],
            ['type' => 'genres', 'id' => '2', 'attributes' => ['name' => 'Fantasy']],
            [
                'type' => 'mediaStaff',
                'id' => '151',
                'attributes' => ['role' => 'Story & Art'],
                'relationships' => ['person' => ['data' => ['type' => 'people', 'id' => '9']]],
            ],
            ['type' => 'people', 'id' => '9', 'attributes' => ['name' => 'Kentaro Miura']],
        ];
    }

    private function malPayload(): array
    {
        return [
            'id' => 2,
            'title' => 'Berserk',
            'main_picture' => ['large' => 'https://example.com/berserk.jpg'],
            'alternative_titles' => ['synonyms' => ['Beruseruku'], 'en' => 'Berserk', 'ja' => 'ベルセルク'],
            'synopsis' => 'Guts.',
            'mean' => 9.47,
            'status' => 'finished',
            'media_type' => 'manga',
            'num_chapters' => 376,
            'num_volumes' => 42,
            'start_date' => '1989-08-25',
            'end_date' => '2021-09-10',
            'genres' => [['id' => 1, 'name' => 'Action'], ['id' => 8, 'name' => 'Drama']],
            'authors' => [['node' => ['id' => 1280, 'first_name' => 'Kentaro', 'last_name' => 'Miura'], 'role' => 'Story & Art']],
        ];
    }

    private function mangadexPayload(): array
    {
        return [
            'id' => 'manga-1',
            'type' => 'manga',
            'attributes' => [
                'title' => ['en' => 'Berserk', 'ja' => 'ベルセルク'],
                'description' => ['en' => 'Guts.'],
                'status' => 'ongoing',
                'year' => 1989,
                'lastChapter' => '376',
                'lastVolume' => '42',
                'contentRating' => 'safe',
                'tags' => [
                    ['id' => 'g1', 'attributes' => ['group' => 'genre', 'name' => ['en' => 'Action']]],
                    ['id' => 'g2', 'attributes' => ['group' => 'genre', 'name' => ['en' => 'Drama']]],
                    ['id' => 'c1', 'attributes' => ['group' => 'content', 'name' => ['en' => 'Gore']]],
                ],
            ],
            'relationships' => [
                ['id' => 'a1', 'type' => 'author', 'attributes' => ['name' => 'Kentaro Miura']],
                ['id' => 'co1', 'type' => 'cover_art', 'attributes' => ['fileName' => 'cover.jpg']],
            ],
        ];
    }
}

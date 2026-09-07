<?php

namespace Tests\Feature\Yomi;

use App\Models\User;
use App\Modules\Yomi\Models\Manga;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class DiscoverTest extends TestCase
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

        config(['yomi.providers.kitsu.enabled' => true]);
        config(['yomi.provider_priority' => ['kitsu', 'jikan', 'anilist']]);

        $this->user = User::factory()->create();

        $this->withoutMiddleware(\App\Modules\Metricas\Http\Middleware\RegistrarAcesso::class);
    }

    public function test_titulo_e_capa_apontam_para_pagina_de_detalhes_em_vez_da_imagem(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => [$this->kitsuPayload()],
                'included' => $this->kitsuIncluded(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover'))
            ->assertOk()
            ->assertSee('/yomi/descobrir/kitsu/123', false)
            ->assertSee('+ Adicionar')
            ->assertDontSee('href="https://example.com/poster.jpg"', false);
    }

    public function test_pagina_de_detalhes_externa_mostra_obra(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => $this->kitsuPayload(),
                'included' => $this->kitsuIncluded(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '123']))
            ->assertOk()
            ->assertSee('Berserk')
            ->assertSee('Adicionar à estante')
            ->assertSee('Action')
            ->assertSee('Kentaro Miura');
    }

    public function test_detalhes_redirecionam_para_obra_local_quando_ja_na_estante(): void
    {
        $manga = Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);
        $manga->externalIds()->create(['provider' => 'kitsu', 'external_id' => '123']);

        Http::fake();

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '123']))
            ->assertRedirect(route('yomi.mangas.show', $manga));

        Http::assertNothingSent();
    }

    public function test_detalhes_com_provedor_invalido_retorna_404(): void
    {
        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'desconhecido', 'externalId' => '123']))
            ->assertNotFound();
    }

    public function test_adicionar_manga_do_kitsu_cadastra_e_marca_pretendo_ler(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => $this->kitsuPayload(),
                'included' => $this->kitsuIncluded(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Berserk']);
        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'status' => 'pretendo_ler',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/manga/123')
            && str_contains($request->url(), 'staff.person'));
    }

    public function test_library_mostra_capa_remota_apos_adicionar_manga(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => $this->kitsuPayload(),
                'included' => $this->kitsuIncluded(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
            ])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('yomi.library'))
            ->assertOk()
            ->assertSee('https://example.com/poster.jpg', false);
    }

    public function test_adicionar_manga_ja_existente_marca_pretendo_ler_sem_nova_obra(): void
    {
        $manga = Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);
        $manga->externalIds()->create(['provider' => 'kitsu', 'external_id' => '123']);

        Http::fake();

        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
            ])
            ->assertRedirect(route('yomi.mangas.show', $manga));

        $this->assertDatabaseCount('yomi_mangas', 1);
        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'manga_id' => $manga->id,
            'status' => 'pretendo_ler',
        ]);

        Http::assertNothingSent();
    }

    public function test_adicionar_manga_novo_com_status_lendo(): void
    {
        Http::fake([
            'kitsu.io/*' => Http::response([
                'data' => $this->kitsuPayload(),
                'included' => $this->kitsuIncluded(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
                'status' => 'lendo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Berserk']);
        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'status' => 'lendo',
        ]);
    }

    public function test_adicionar_manga_ja_existente_com_status_concluido(): void
    {
        $manga = Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);
        $manga->externalIds()->create(['provider' => 'kitsu', 'external_id' => '123']);

        Http::fake();

        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
                'status' => 'concluido',
            ])
            ->assertRedirect(route('yomi.mangas.show', $manga));

        $this->assertDatabaseHas('yomi_progresso_usuario', [
            'user_id' => $this->user->id,
            'manga_id' => $manga->id,
            'status' => 'concluido',
        ]);

        Http::assertNothingSent();
    }

    public function test_adicionar_com_status_invalido_falha_na_validacao(): void
    {
        $this->actingAs($this->user)
            ->post(route('yomi.discover.register'), [
                'provider' => 'kitsu',
                'external_id' => '123',
                'status' => 'status_invalido',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_pagina_de_detalhes_externa_exibe_capitulos_do_kitsu(): void
    {
        Http::fake([
            'kitsu.io/api/edge/manga/123*' => Http::response([
                'data' => $this->kitsuPayload(['attributes' => array_merge($this->kitsuPayload()['attributes'], ['chapterCount' => null])]),
                'included' => $this->kitsuIncluded(),
            ]),
            'kitsu.io/api/edge/chapters*' => Http::response([
                'data' => [
                    [
                        'id' => 'c1',
                        'type' => 'chapters',
                        'attributes' => [
                            'number' => 1,
                            'canonicalTitle' => 'O Começo da Jornada',
                            'published' => '1989-08-25',
                        ],
                    ],
                    [
                        'id' => 'c2',
                        'type' => 'chapters',
                        'attributes' => [
                            'number' => 2,
                            'canonicalTitle' => 'O Espadachim Negro',
                            'published' => '1989-09-25',
                        ],
                    ],
                ],
                'meta' => ['count' => 2],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '123']))
            ->assertOk()
            ->assertSee('Capítulos (2)')
            ->assertSee('O Começo da Jornada')
            ->assertSee('O Espadachim Negro')
            ->assertSee('25/08/1989');
    }

    public function test_pagina_de_detalhes_externa_kitsu_24147_exibe_capitulos(): void
    {
        Http::fake([
            'kitsu.io/api/edge/manga/24147*' => Http::response([
                'data' => $this->kitsuPayload([
                    'id' => '24147',
                    'attributes' => array_merge($this->kitsuPayload()['attributes'], [
                        'canonicalTitle' => 'Gokusai no Ie',
                        'chapterCount' => 12,
                    ]),
                ]),
                'included' => $this->kitsuIncluded(),
            ]),
            'kitsu.io/api/edge/chapters*' => Http::response([
                'data' => [],
                'meta' => ['count' => 0],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '24147']))
            ->assertOk()
            ->assertSee('Gokusai no Ie')
            ->assertSee('Capítulos (12)')
            ->assertSee('Capítulo 1')
            ->assertSee('Capítulo 12');
    }

    public function test_pagina_de_detalhes_externa_usa_fallback_mangadex_quando_kitsu_sem_capitulos(): void
    {
        config(['yomi.providers.mangadex.enabled' => true]);

        Http::fake([
            'kitsu.io/api/edge/manga/24147*' => Http::response([
                'data' => $this->kitsuPayload([
                    'id' => '24147',
                    'attributes' => array_merge($this->kitsuPayload()['attributes'], [
                        'canonicalTitle' => 'Gokusai no Ie',
                        'chapterCount' => null,
                    ]),
                ]),
                'included' => [],
            ]),
            'kitsu.io/api/edge/chapters*' => Http::response(['data' => []]),
            'api.mangadex.org/manga?*' => Http::response([
                'data' => [
                    [
                        'id' => 'dex-gokusai',
                        'type' => 'manga',
                        'attributes' => [
                            'title' => ['en' => 'Gokusai no Ie'],
                        ],
                    ],
                ],
            ]),
            'api.mangadex.org/manga/dex-gokusai/feed*' => Http::response([
                'total' => 1,
                'data' => [
                    [
                        'id' => 'dex-chap-1',
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'Cores Extremas',
                            'publishAt' => '2020-01-01T00:00:00+00:00',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '24147']))
            ->assertOk()
            ->assertSee('Capítulos (1)')
            ->assertSee('Cores Extremas');
    }

    public function test_pagina_de_detalhes_externa_respeita_ordem_descendente(): void
    {
        Http::fake([
            'kitsu.io/api/edge/manga/123*' => Http::response([
                'data' => $this->kitsuPayload(['attributes' => array_merge($this->kitsuPayload()['attributes'], ['chapterCount' => null])]),
                'included' => $this->kitsuIncluded(),
            ]),
            'kitsu.io/api/edge/chapters*' => Http::response([
                'data' => [
                    [
                        'id' => 'c1',
                        'type' => 'chapters',
                        'attributes' => ['number' => 1, 'canonicalTitle' => 'Capítulo Um'],
                    ],
                    [
                        'id' => 'c2',
                        'type' => 'chapters',
                        'attributes' => ['number' => 2, 'canonicalTitle' => 'Capítulo Dois'],
                    ],
                ],
                'meta' => ['count' => 2],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'kitsu', 'externalId' => '123', 'ordem' => 'desc']))
            ->assertOk();

        $content = (string) $response->getContent();
        $posDois = strpos($content, 'Capítulo Dois');
        $posUm = strpos($content, 'Capítulo Um');

        $this->assertTrue($posDois !== false && $posUm !== false && $posDois < $posUm);
    }

    public function test_pagina_de_detalhes_externa_jikan_exibe_capitulos(): void
    {
        config(['yomi.providers.mangadex.enabled' => true]);

        Http::fake([
            'api.jikan.moe/v4/manga/123/full' => Http::response(['data' => $this->jikanPayload()]),
            'api.jikan.moe/v4/manga/123*' => Http::response(['data' => $this->jikanPayload()]),
            'api.mangadex.org/manga?*' => Http::response([
                'data' => [
                    [
                        'id' => 'dex-berserk',
                        'type' => 'manga',
                        'attributes' => ['title' => ['en' => 'Berserk']],
                    ],
                ],
            ]),
            'api.mangadex.org/manga/dex-berserk/feed*' => Http::response([
                'total' => 1,
                'data' => [
                    [
                        'id' => 'chap-1',
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'O Espadachim Negro',
                            'publishAt' => '1989-08-25T00:00:00+00:00',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'jikan', 'externalId' => '123']))
            ->assertOk()
            ->assertSee('Berserk')
            ->assertSee('Capítulos (1)')
            ->assertSee('O Espadachim Negro');
    }

    public function test_pagina_de_detalhes_externa_anilist_exibe_capitulos(): void
    {
        Http::fake([
            'graphql.anilist.co*' => Http::response([
                'data' => [
                    'Media' => [
                        'id' => 999,
                        'idMal' => 123,
                        'title' => [
                            'romaji' => 'Sousou no Frieren',
                            'english' => 'Frieren: Beyond Journey\'s End',
                            'native' => '葬送のフリーレン',
                        ],
                        'description' => 'A jornada de Frieren.',
                        'status' => 'RELEASING',
                        'format' => 'MANGA',
                        'chapters' => 130,
                        'volumes' => 12,
                        'startDate' => ['year' => 2020, 'month' => 4, 'day' => 28],
                        'endDate' => ['year' => null, 'month' => null, 'day' => null],
                        'genres' => ['Adventure', 'Drama', 'Fantasy'],
                        'averageScore' => 90,
                        'coverImage' => ['extraLarge' => 'https://cdn.example.com/frieren.jpg'],
                        'staff' => ['edges' => []],
                        'characters' => ['edges' => []],
                    ],
                ],
            ]),
            'api.mangadex.org/*' => Http::response(['data' => []]),
            'kitsu.io/*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'anilist', 'externalId' => '999']))
            ->assertOk()
            ->assertSee('Sousou no Frieren')
            ->assertSee('Capítulos (130)')
            ->assertSee('Capítulo 1')
            ->assertSee('Capítulo 130');
    }

    public function test_pagina_de_detalhes_externa_mangadex_exibe_capitulos(): void
    {
        config(['yomi.providers.mangadex.enabled' => true]);

        Http::fake([
            'api.mangadex.org/manga/dex-uuid/feed*' => Http::response([
                'total' => 2,
                'data' => [
                    [
                        'id' => 'chap-1',
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'O Início',
                            'publishAt' => '2021-01-01T00:00:00+00:00',
                        ],
                    ],
                    [
                        'id' => 'chap-2',
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '2',
                            'title' => 'O Encontro',
                            'publishAt' => '2021-01-08T00:00:00+00:00',
                        ],
                    ],
                ],
            ]),
            'api.mangadex.org/manga/dex-uuid*' => Http::response([
                'data' => [
                    'id' => 'dex-uuid',
                    'type' => 'manga',
                    'attributes' => [
                        'title' => ['en' => 'Chainsaw Man'],
                        'description' => ['en' => 'Denji the chainsaw guy.'],
                        'status' => 'ongoing',
                        'contentRating' => 'suggestive',
                        'lastChapter' => '150',
                    ],
                    'relationships' => [],
                ],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'mangadex', 'externalId' => 'dex-uuid']))
            ->assertOk()
            ->assertSee('Chainsaw Man')
            ->assertSee('Capítulos (2)')
            ->assertSee('O Início')
            ->assertSee('O Encontro');
    }

    public function test_pagina_de_detalhes_externa_mal_exibe_capitulos(): void
    {
        config(['yomi.providers.mal.enabled' => true]);

        Http::fake([
            'api.myanimelist.net/v2/manga/2*' => Http::response([
                'id' => 2,
                'title' => 'Berserk',
                'alternative_titles' => ['en' => 'Berserk', 'ja' => 'ベルセルク', 'synonyms' => []],
                'synopsis' => 'Guts, a former mercenary...',
                'status' => 'currently_publishing',
                'media_type' => 'manga',
                'num_chapters' => 376,
                'num_volumes' => 42,
                'start_date' => '1989-08-25',
                'mean' => 9.47,
                'main_picture' => ['large' => 'https://cdn.myanimelist.net/berserk.jpg'],
                'genres' => [['id' => 1, 'name' => 'Action']],
                'authors' => [],
            ]),
            'api.mangadex.org/*' => Http::response(['data' => []]),
            'kitsu.io/*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->user)
            ->get(route('yomi.discover.external', ['provider' => 'mal', 'externalId' => '2']))
            ->assertOk()
            ->assertSee('Berserk')
            ->assertSee('Capítulos (376)')
            ->assertSee('Capítulo 1')
            ->assertSee('Capítulo 376');
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

    private function kitsuPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
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
}

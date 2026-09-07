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

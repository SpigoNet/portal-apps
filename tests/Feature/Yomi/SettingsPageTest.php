<?php

namespace Tests\Feature\Yomi;

use App\Models\User;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Services\SyncManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class SettingsPageTest extends TestCase
{
    use RefreshYomiDatabase;

    private User $owner;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        Queue::fake();

        config(['yomi.providers.jikan.throttle_ms' => 0]);
        config(['yomi.providers.anilist.throttle_ms' => 0]);
        config(['yomi.providers.jikan.retry.tries' => 1]);
        config(['yomi.providers.anilist.retry.tries' => 1]);

        $this->owner = User::factory()->create([
            'id' => (int) config('yomi.owner_user_id', 1),
        ]);
        $this->other = User::factory()->create(['id' => 99]);

        $this->withoutMiddleware(\App\Modules\Metricas\Http\Middleware\RegistrarAcesso::class);
    }

    public function test_dono_pode_ver_configuracoes(): void
    {
        $this->actingAs($this->owner)
            ->get(route('yomi.settings'))
            ->assertOk()
            ->assertSee('Configurações')
            ->assertSee('Prioridade');
    }

    public function test_outro_usuario_recebe_404(): void
    {
        $this->actingAs($this->other)
            ->get(route('yomi.settings'))
            ->assertNotFound();
    }

    public function test_deslogado_nao_ve_formulario(): void
    {
        $this->get(route('yomi.settings'))
            ->assertRedirect(route('login'));
    }

    public function test_dono_salva_configuracoes(): void
    {
        $this->actingAs($this->owner)
            ->post(route('yomi.settings.update'), [
                'priority' => ['anilist', 'jikan'],
                'providers' => [
                    'jikan' => ['enabled' => true, 'base_url' => 'https://my-proxy.example/v4'],
                    'anilist' => ['enabled' => true, 'base_url' => ''],
                ],
            ])
            ->assertRedirect(route('yomi.settings'));

        $this->assertDatabaseHas('yomi_settings', ['key' => 'provider_priority']);
        $this->assertSame(
            ['anilist', 'jikan'],
            app(\App\Modules\Yomi\Services\YomiSettingsService::class)->orderedNames(),
        );
    }

    public function test_dono_salva_credenciais_dos_provedores(): void
    {
        $this->actingAs($this->owner)
            ->post(route('yomi.settings.update'), [
                'priority' => ['anilist', 'jikan', 'kitsu', 'mal', 'mangadex'],
                'providers' => [
                    'jikan' => ['enabled' => true, 'base_url' => ''],
                    'anilist' => ['enabled' => true, 'base_url' => '', 'credentials' => ['token' => 'tok-abc']],
                    'kitsu' => ['enabled' => true, 'base_url' => ''],
                    'mal' => ['enabled' => true, 'base_url' => '', 'credentials' => ['client_id' => 'cid-xyz']],
                    'mangadex' => ['enabled' => true, 'base_url' => ''],
                ],
            ])
            ->assertRedirect(route('yomi.settings'));

        $service = app(\App\Modules\Yomi\Services\YomiSettingsService::class);

        $this->assertSame('tok-abc', $service->providerConfig(\App\Modules\Yomi\Enums\ProviderName::AniList)['credentials']['token']);
        $this->assertSame('cid-xyz', $service->providerConfig(\App\Modules\Yomi\Enums\ProviderName::Mal)['credentials']['client_id']);
    }

    public function test_formulario_exibe_os_cinco_provedores_e_campos_de_credenciais(): void
    {
        app(\App\Modules\Yomi\Services\YomiSettingsService::class)->save([
            'priority' => ['anilist', 'jikan'],
            'providers' => [
                'jikan' => ['enabled' => true, 'base_url' => null],
                'anilist' => ['enabled' => true, 'base_url' => null, 'credentials' => ['token' => 'tok-salvo']],
            ],
        ]);

        $this->actingAs($this->owner)
            ->get(route('yomi.settings'))
            ->assertOk()
            ->assertSee('Jikan/MAL')
            ->assertSee('AniList')
            ->assertSee('Kitsu')
            ->assertSee('MyAnimeList')
            ->assertSee('MangaDex')
            ->assertSee('Access Token')
            ->assertSee('Client ID')
            ->assertSee('tok-salvo')
            ->assertSee('Verificar estado de saúde');
    }

    public function test_dono_pode_verificar_saude_de_provedor(): void
    {
        config(['yomi.providers.kitsu.enabled' => true]);
        config(['yomi.providers.kitsu.throttle_ms' => 0]);
        config(['yomi.providers.kitsu.retry.tries' => 1]);

        Http::fake(['kitsu.io/*' => Http::response(['data' => []], 200)]);

        $this->actingAs($this->owner)
            ->get(route('yomi.settings.providers.health', ['provider' => 'kitsu']))
            ->assertOk()
            ->assertJsonPath('provider', 'kitsu')
            ->assertJsonPath('status', 'ok');
    }

    public function test_outro_usuario_nao_verifica_saude(): void
    {
        $this->actingAs($this->other)
            ->get(route('yomi.settings.providers.health', ['provider' => 'kitsu']))
            ->assertNotFound();
    }

    public function test_saude_de_provedor_invalido_retorna_422(): void
    {
        $this->actingAs($this->owner)
            ->get(route('yomi.settings.providers.health', ['provider' => 'inexistente']))
            ->assertStatus(422);
    }

    public function test_outro_usuario_nao_pode_salvar(): void
    {
        $this->actingAs($this->other)
            ->post(route('yomi.settings.update'), [
                'priority' => ['anilist', 'jikan'],
                'providers' => [
                    'jikan' => ['enabled' => true, 'base_url' => ''],
                    'anilist' => ['enabled' => true, 'base_url' => ''],
                ],
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('yomi_settings', ['key' => 'provider_priority']);
    }

    public function test_rejeita_prioridade_repetida(): void
    {
        $this->actingAs($this->owner)
            ->post(route('yomi.settings.update'), [
                'priority' => ['jikan', 'jikan'],
                'providers' => [
                    'jikan' => ['enabled' => true, 'base_url' => ''],
                    'anilist' => ['enabled' => true, 'base_url' => ''],
                ],
            ])
            ->assertSessionHasErrors('priority');
    }

    public function test_rejeita_todos_provedores_desativados(): void
    {
        $this->actingAs($this->owner)
            ->post(route('yomi.settings.update'), [
                'priority' => ['jikan', 'anilist'],
                'providers' => [
                    'jikan' => ['enabled' => false, 'base_url' => ''],
                    'anilist' => ['enabled' => false, 'base_url' => ''],
                ],
            ])
            ->assertSessionHasErrors('providers');
    }

    public function test_provedor_desativado_nao_e_consultado_no_fallback(): void
    {
        app(\App\Modules\Yomi\Services\YomiSettingsService::class)->save([
            'priority' => ['anilist', 'jikan'],
            'providers' => [
                'jikan' => ['enabled' => false, 'base_url' => null],
                'anilist' => ['enabled' => true, 'base_url' => null],
            ],
        ]);

        $manga = Manga::create(['titulo' => 'Berserk', 'status_publicacao' => 'completo', 'sync_status' => 'sincronizado']);
        $manga->externalIds()->create(['provider' => 'anilist', 'external_id' => '999']);

        Http::fake([
            'graphql.anilist.co/*' => Http::response(['data' => ['Media' => $this->anilistPayload()]]),
        ]);

        $result = app(SyncManager::class)->getOrSync(externalId: '123', provider: 'jikan');

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Fanged Bleach']);
        $this->assertSame('anilist', $result->provider->value);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'jikan.moe'));
    }

    public function test_rota_config_usar_layout_do_modulo(): void
    {
        $this->actingAs($this->owner)
            ->get('/yomi/configuracoes')
            ->assertOk()
            ->assertSee('yomi-body')
            ->assertSee('>Yomi<', false)
            ->assertSee('Salvar configurações')
            ->assertSee('alpinejs@3.x.x', false);
    }

    public function test_salva_provedor_ativado_sem_prioridade_submetida(): void
    {
        $this->actingAs($this->owner)
            ->post(route('yomi.settings.update'), [
                'providers' => [
                    'jikan' => ['enabled' => true, 'base_url' => ''],
                    'anilist' => ['enabled' => true, 'base_url' => ''],
                    'kitsu' => ['enabled' => true, 'base_url' => ''],
                    'mal' => ['enabled' => false, 'base_url' => ''],
                    'mangadex' => ['enabled' => false, 'base_url' => ''],
                ],
            ])
            ->assertRedirect(route('yomi.settings'));

        $service = app(\App\Modules\Yomi\Services\YomiSettingsService::class);

        $this->assertTrue($service->isEnabled(\App\Modules\Yomi\Enums\ProviderName::Kitsu));
        $this->assertSame(
            ['jikan', 'anilist', 'kitsu'],
            $service->orderedNames(),
        );
    }

    private function anilistPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 999,
            'idMal' => 123,
            'title' => ['romaji' => 'Fanged Bleach', 'english' => 'Fanged Bleach (EN)', 'native' => '牙をむく'],
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

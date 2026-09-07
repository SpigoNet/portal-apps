<?php

namespace Tests\Unit\Yomi;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Models\YomiSetting;
use App\Modules\Yomi\Services\YomiSettingsService;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class SettingsServiceTest extends TestCase
{
    use RefreshYomiDatabase;

    private YomiSettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        $this->settings = app(YomiSettingsService::class);
    }

    public function test_prio_padrao_usa_config_quando_nada_armazenado(): void
    {
        $this->assertSame(
            ['jikan', 'anilist'],
            array_map(fn (ProviderName $p): string => $p->value, $this->settings->priority()),
        );
    }

    public function test_save_persiste_prioridade_e_estado(): void
    {
        $this->settings->save([
            'priority' => ['anilist', 'jikan'],
            'providers' => [
                'jikan' => ['enabled' => true, 'base_url' => null],
                'anilist' => ['enabled' => true, 'base_url' => ''],
            ],
        ]);

        $this->assertSame(
            ['anilist', 'jikan'],
            array_map(fn (ProviderName $p): string => $p->value, $this->settings->priority()),
        );
        $this->assertDatabaseHas('yomi_settings', ['key' => 'provider_priority']);
        $this->assertDatabaseHas('yomi_settings', ['key' => 'providers']);
    }

    public function test_provedor_desativado_nao_aparece_na_prioridade(): void
    {
        $state = collect(ProviderName::cases())->mapWithKeys(
            fn (ProviderName $p): array => [
                $p->value => ['enabled' => $p === ProviderName::Jikan, 'base_url' => null],
            ],
        )->all();

        $this->settings->write('providers', $state);
        $this->settings->write('provider_priority', ['anilist', 'jikan']);

        $this->assertSame(['jikan'], $this->settings->orderedNames());
    }

    public function test_provider_config_merge_apenas_base_url_e_preserva_defaults(): void
    {
        YomiSetting::query()->updateOrCreate(['key' => 'providers'], [
            'value' => ['jikan' => ['enabled' => true, 'base_url' => 'https://my-proxy.example/v4']],
        ]);

        $config = $this->settings->providerConfig(ProviderName::Jikan);

        $this->assertSame('https://my-proxy.example/v4', $config['base_url']);
        $this->assertSame((int) config('yomi.providers.jikan.timeout'), $config['timeout']);
        $this->assertTrue($config['enabled']);
    }

    public function test_provider_config_vazio_cai_para_base_url_padrao(): void
    {
        YomiSetting::query()->updateOrCreate(['key' => 'providers'], [
            'value' => ['jikan' => ['enabled' => true, 'base_url' => null]],
        ]);

        $config = $this->settings->providerConfig(ProviderName::Jikan);

        $this->assertSame(config('yomi.providers.jikan.base_url'), $config['base_url']);
    }

    public function test_reset_limpa_configuracoes(): void
    {
        $this->settings->save([
            'priority' => ['jikan'],
            'providers' => ['jikan' => ['enabled' => true, 'base_url' => null]],
        ]);

        $this->settings->reset();

        $this->assertSame(0, YomiSetting::query()->count());
        $this->assertSame(['jikan', 'anilist'], $this->settings->orderedNames());
    }

    public function test_novos_provedores_desativados_por_padrao(): void
    {
        $this->assertSame(['jikan', 'anilist'], $this->settings->orderedNames());
        $this->assertTrue($this->settings->isEnabled(ProviderName::Jikan));
        $this->assertTrue($this->settings->isEnabled(ProviderName::AniList));
        $this->assertFalse($this->settings->isEnabled(ProviderName::Kitsu));
        $this->assertFalse($this->settings->isEnabled(ProviderName::Mal));
        $this->assertFalse($this->settings->isEnabled(ProviderName::MangaDex));
    }

    public function test_credenciais_sao_persistidas_e_resolvidas(): void
    {
        $this->settings->save([
            'priority' => ['anilist'],
            'providers' => [
                'anilist' => ['enabled' => true, 'base_url' => null, 'credentials' => ['token' => 'sekret']],
            ],
        ]);

        $config = $this->settings->providerConfig(ProviderName::AniList);

        $this->assertSame('sekret', $config['credentials']['token']);
        $this->assertSame('https://graphql.anilist.co', $config['base_url']);
    }

    public function test_credencial_vazia_cai_para_o_config_padrao(): void
    {
        config(['yomi.providers.mal.credentials.client_id' => 'env-client']);

        $config = $this->settings->providerConfig(ProviderName::Mal);

        $this->assertSame('env-client', $config['credentials']['client_id']);
    }

    public function test_provedores_sem_credenciais_retornam_lista_vazia(): void
    {
        $config = $this->settings->providerConfig(ProviderName::Kitsu);

        $this->assertSame([], $config['credentials']);
    }
}

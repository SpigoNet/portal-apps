<?php

namespace Tests\Unit\Yomi;

use App\Models\User;
use App\Modules\Yomi\DTOs\ExternalCharacter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\StatusLeitura;
use App\Modules\Yomi\Models\Capitulo;
use App\Modules\Yomi\Models\Genero;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\ProgressoUsuario;
use App\Modules\Yomi\Models\UsuarioCapitulo;
use App\Modules\Yomi\Services\MangaService;
use App\Modules\Yomi\Services\ProgressService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Unit\Yomi\Concerns\RefreshYomiDatabase;

class DomainTest extends TestCase
{
    use RefreshYomiDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshYomiDatabase();

        $this->mangaService = app(MangaService::class);
        $this->progressService = app(ProgressService::class);
    }

    public function test_cadastra_obra_com_relacionamentos(): void
    {
        $external = $this->externalManga();

        $manga = $this->mangaService->createFromExternal($external, ProviderName::Jikan);

        $this->assertDatabaseHas('yomi_mangas', ['titulo' => 'Berserk']);
        $this->assertDatabaseHas('yomi_manga_external_ids', [
            'manga_id' => $manga->id,
            'provider' => 'jikan',
            'external_id' => '123',
        ]);
        $this->assertTrue($manga->generos()->where('nome', 'Action')->exists());
        $this->assertTrue($manga->criadores()->where('nome', 'Kentaro Miura')->exists());
        $this->assertTrue($manga->personagens()->where('nome', 'Guts')->exists());
        $this->assertTrue($manga->titulos()->where('titulo', 'Berserk (EN)')->exists());
    }

    public function test_criadores_e_personagens_sao_persistidos_sem_duplicacao(): void
    {
        $first = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);
        $second = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->assertSame(1, $first->criadores()->count());
        $this->assertSame(1, $second->criadores()->count());
        $this->assertSame(1, $first->personagens()->count());
        $this->assertSame(1, $second->personagens()->count());
        $this->assertSame(1, \App\Modules\Yomi\Models\Criador::count());
        $this->assertSame(1, \App\Modules\Yomi\Models\Personagem::count());
    }

    public function test_generos_sao_normalizados_entre_obras(): void
    {
        $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);
        $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->assertSame(2, Genero::count());
        $this->assertSame(1, Genero::where('nome', 'Action')->count());
    }

    public function test_progresso_do_usuario_e_unico_por_obra(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->progressService->updateNotes($user->id, $manga->id, 'ottimo');
        $this->progressService->rate($user->id, $manga->id, 9);

        $this->assertSame(1, ProgressoUsuario::where('user_id', $user->id)->where('manga_id', $manga->id)->count());
    }

    public function test_status_e_progresso_granular(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);
        $capitulo = Capitulo::create([
            'manga_id' => $manga->id,
            'numero' => 1,
            'titulo' => 'Capítulo 1',
            'provider' => 'jikan',
        ]);

        $this->progressService->setStatus($user->id, $manga->id, StatusLeitura::Lendo);
        $this->progressService->markChapterRead($user->id, $manga->id, $capitulo->id);

        $progresso = $this->progressService->getForUser($user->id, $manga->id);

        $this->assertSame(StatusLeitura::Lendo, $progresso->status);
        $this->assertSame(1.0, $progresso->ultimo_capitulo_lido);
        $this->assertTrue($this->progressService->isChapterRead($user->id, $capitulo->id));
        $this->assertSame(1, UsuarioCapitulo::count());
    }

    public function test_marcar_capitulo_repetido_nao_duplica(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);
        $capitulo = Capitulo::create(['manga_id' => $manga->id, 'numero' => 2]);

        $this->progressService->markChapterRead($user->id, $manga->id, $capitulo->id);
        $this->progressService->markChapterRead($user->id, $manga->id, $capitulo->id);

        $this->assertSame(1, UsuarioCapitulo::where('user_id', $user->id)->where('capitulo_id', $capitulo->id)->count());
    }

    public function test_nota_favorito_e_observacoes(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->progressService->rate($user->id, $manga->id, 10);
        $this->progressService->setFavorite($user->id, $manga->id, true);
        $this->progressService->updateNotes($user->id, $manga->id, 'Leitura obrigatória');

        $progresso = $this->progressService->getForUser($user->id, $manga->id);

        $this->assertSame(10, $progresso->nota);
        $this->assertTrue($progresso->favorito);
        $this->assertSame('Leitura obrigatória', $progresso->observacoes);
    }

    public function test_favorito_alterna_estado(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->progressService->setFavorite($user->id, $manga->id, true);
        $this->progressService->toggleFavorite($user->id, $manga->id);

        $this->assertFalse($this->progressService->getForUser($user->id, $manga->id)->favorito);
    }

    public function test_concluir_obra_registra_data_de_conclusao(): void
    {
        $user = User::factory()->create();
        $manga = $this->mangaService->createFromExternal($this->externalManga(), ProviderName::Jikan);

        $this->progressService->setStatus($user->id, $manga->id, StatusLeitura::Concluido);

        $this->assertNotNull($this->progressService->getForUser($user->id, $manga->id)->completed_at);
    }

    public function test_media_capa_exibe_url_remota_quando_nao_baixada(): void
    {
        $manga = $this->manga();
        $midia = $manga->midias()->create([
            'type' => 'capa',
            'source_url' => 'https://example.com/poster.jpg',
            'status' => 'pendente',
        ]);

        $this->assertSame('https://example.com/poster.jpg', $midia->displayUrl());
    }

    public function test_media_capa_exibe_url_local_quando_espelho_existe(): void
    {
        Storage::fake('local');

        $manga = $this->manga();
        $midia = $manga->midias()->create([
            'type' => 'capa',
            'source_url' => 'https://example.com/poster.jpg',
            'storage_path' => 'yomi/manga/capa/1/capa.jpg',
            'status' => 'baixada',
        ]);
        Storage::disk('local')->put('yomi/manga/capa/1/capa.jpg', 'conteudo');

        $this->assertStringContainsString('yomi/manga/capa/1/capa.jpg', (string) $midia->displayUrl());
    }

    public function test_media_capa_volta_para_url_remota_quando_espelho_eh_removido(): void
    {
        Storage::fake('local');

        $manga = $this->manga();
        $midia = $manga->midias()->create([
            'type' => 'capa',
            'source_url' => 'https://example.com/poster.jpg',
            'storage_path' => 'yomi/manga/capa/1/capa.jpg',
            'status' => 'baixada',
        ]);

        $this->assertSame('https://example.com/poster.jpg', $midia->displayUrl());
    }

    private function manga(): Manga
    {
        return Manga::create([
            'titulo' => 'Berserk',
            'status_publicacao' => 'completo',
            'sync_status' => 'sincronizado',
        ]);
    }

    private function externalManga(): ExternalManga
    {
        return new ExternalManga(
            title: 'Berserk',
            originalTitle: 'ベルセルク',
            synopsis: 'Um espadachim das trevas.',
            status: 'completo',
            type: 'manga',
            chapters: 364,
            volumes: 40,
            publishedFrom: '1989-01-01',
            publishedTo: '2021-09-10',
            score: 9.5,
            imageUrl: 'https://cdn.example.com/berserk.jpg',
            externalIds: [['provider' => 'jikan', 'external_id' => '123']],
            genres: ['Action', 'Fantasy'],
            alternativeTitles: ['Berserk (EN)', 'Kenpū Denki Berserk'],
            creators: [new ExternalCreator(name: 'Kentaro Miura', role: 'Story & Art', externalId: '789')],
            characters: [new ExternalCharacter(name: 'Guts', role: 'main', externalId: '456', imageUrl: 'https://cdn.example.com/guts.jpg')],
        );
    }
}

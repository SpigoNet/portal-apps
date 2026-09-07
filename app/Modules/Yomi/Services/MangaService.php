<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\DTOs\ExternalCharacter;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Repositories\CapituloRepository;
use App\Modules\Yomi\Repositories\CriadorRepository;
use App\Modules\Yomi\Repositories\GeneroRepository;
use App\Modules\Yomi\Repositories\MangaRepository;
use App\Modules\Yomi\Repositories\PersonagemRepository;
use Illuminate\Support\Facades\DB;

class MangaService
{
    public function __construct(
        protected MangaRepository $mangaRepository,
        protected CriadorRepository $criadorRepository,
        protected PersonagemRepository $personagemRepository,
        protected GeneroRepository $generoRepository,
        protected CapituloRepository $capituloRepository,
    ) {}

    public function createFromExternal(ExternalManga $external, ProviderName $provider): Manga
    {
        return DB::transaction(function () use ($external, $provider) {
            $manga = Manga::create([
                'titulo' => $external->title,
                'titulo_original' => $external->originalTitle,
                'sinopse' => $external->synopsis,
                'status_publicacao' => $external->status ?? 'desconhecido',
                'tipo' => $external->type,
                'capitulos_conhecidos' => $external->chapters,
                'volumes_conhecidos' => $external->volumes,
                'data_inicio' => $external->publishedFrom,
                'data_fim' => $external->publishedTo,
                'nota_media' => $external->score,
                'classificacao_etaria' => $external->ageRating,
                'fonte_original' => $provider->value,
                'sync_status' => 'pendente',
            ]);

            $this->attachRelations($manga, $external, $provider);

            return $manga;
        });
    }

    public function updateFromExternal(Manga $manga, ExternalManga $external, ProviderName $provider): Manga
    {
        return DB::transaction(function () use ($manga, $external, $provider) {
            $manga->update($this->mergeSyncableFields($manga, $external, $provider));

            $this->attachRelations($manga, $external, $provider);

            return $manga->fresh();
        });
    }

    /**
     * Atualiza campos sincronizáveis sem substituir valores locais válidos por null.
     */
    private function mergeSyncableFields(Manga $manga, ExternalManga $external, ProviderName $provider): array
    {
        $fields = [
            'titulo_original' => $external->originalTitle,
            'sinopse' => $external->synopsis,
            'status_publicacao' => $external->status,
            'tipo' => $external->type,
            'capitulos_conhecidos' => $external->chapters,
            'volumes_conhecidos' => $external->volumes,
            'data_inicio' => $external->publishedFrom,
            'data_fim' => $external->publishedTo,
            'nota_media' => $external->score,
            'classificacao_etaria' => $external->ageRating,
            'source_updated_at' => $external->sourceUpdatedAt,
        ];

        return array_merge($fields, static::onlyValid($manga, $fields), [
            'fonte_original' => $provider->value,
        ]);
    }

    private function attachRelations(Manga $manga, ExternalManga $external, ProviderName $provider): void
    {
        foreach ($external->externalIds as $id) {
            $this->mangaRepository->attachExternalId($manga, $id['provider'], $id['external_id']);
        }

        foreach ($external->alternativeTitles as $titulo) {
            $exists = $manga->titulos()->where('titulo', $titulo)->exists();

            if (! $exists && $titulo !== $manga->titulo) {
                $manga->titulos()->create(['titulo' => $titulo, 'tipo' => 'alternativo', 'idioma' => null]);
            }
        }

        foreach ($external->creators as $criadorExterno) {
            $this->attachCreator($manga, $criadorExterno);
        }

        foreach ($external->characters as $personagemExterno) {
            $this->attachCharacter($manga, $personagemExterno);
        }

        $genres = array_diff(
            array_map(fn (string $genre): string => $this->generoRepository->findOrCreate($genre)->id, $external->genres),
            $manga->generos()->pluck('yomi_generos.id')->all(),
        );

        foreach ($genres as $generoId) {
            $manga->generos()->attach($generoId);
        }

        $this->capituloRepository->syncChapters($manga, $external->chapterList, $provider->value);
    }

    private function attachCreator(Manga $manga, ExternalCreator $criadorExterno): void
    {
        $criador = $this->criadorRepository->findOrCreate(
            nome: $criadorExterno->name,
            urlExterna: $criadorExterno->url,
            externalId: $criadorExterno->externalId,
        );

        $exists = $manga->criadores()
            ->wherePivot('criador_id', $criador->id)
            ->wherePivot('papel', $criadorExterno->role)
            ->exists();

        if (! $exists) {
            $manga->criadores()->attach($criador->id, ['papel' => $criadorExterno->role]);
        }
    }

    private function attachCharacter(Manga $manga, ExternalCharacter $personagemExterno): void
    {
        $personagem = $this->personagemRepository->findOrCreate(
            nome: $personagemExterno->name,
            nomeOriginal: $personagemExterno->originalName,
            descricao: $personagemExterno->description,
            urlExterna: $personagemExterno->url,
            externalId: $personagemExterno->externalId,
        );

        $exists = $manga->personagens()
            ->wherePivot('personagem_id', $personagem->id)
            ->wherePivot('papel', $personagemExterno->role)
            ->exists();

        if (! $exists) {
            $manga->personagens()->attach($personagem->id, ['papel' => $personagemExterno->role]);
        }
    }

    /**
     * Preserva valores locais válidos quando o provedor retorna null.
     */
    private static function onlyValid(Manga $manga, array $fields): array
    {
        $preserved = [];

        foreach ($fields as $column => $value) {
            if ($value !== null || $manga->getAttribute($column) === null) {
                continue;
            }

            $preserved[$column] = $manga->getAttribute($column);
        }

        return $preserved;
    }
}

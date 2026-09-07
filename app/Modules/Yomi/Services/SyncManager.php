<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\DTOs\MangaLookupResult;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\SyncOperation;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Exceptions\ProviderUnavailableException;
use App\Modules\Yomi\Exceptions\YomiSyncException;
use App\Modules\Yomi\Jobs\SyncMangaJob;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Providers\ProviderResolver;
use App\Modules\Yomi\Repositories\MangaRepository;
use App\Modules\Yomi\Repositories\SyncLogRepository;
use Throwable;

class SyncManager
{
    public function __construct(
        protected MangaRepository $mangaRepository,
        protected MangaService $mangaService,
        protected MangaSyncService $mangaSyncService,
        protected ProviderResolver $resolver,
        protected SyncLogRepository $syncLogRepository,
        protected MediaService $mediaService,
    ) {}

    /**
     * Ponto de entrada principal: prioriza o banco local e degrada de forma controlada.
     */
    public function getOrSync(
        ?int $mangaId = null,
        ?string $externalId = null,
        ?string $provider = null,
        bool $dispatchInBackground = true,
    ): MangaLookupResult {
        $manga = $this->findLocal($mangaId, $provider, $externalId);

        if ($manga !== null) {
            if (! $manga->isStale((int) config('yomi.sync.stale_after_minutes', 1440))) {
                return new MangaLookupResult(manga: $manga, fromCache: true);
            }

            if ($dispatchInBackground) {
                SyncMangaJob::dispatch($manga->id);

                return new MangaLookupResult(manga: $manga, fromCache: true);
            }

            return new MangaLookupResult(
                manga: $this->mangaSyncService->sync($manga->id),
                fromCache: false,
                synced: true,
            );
        }

        $result = $this->createFromProviders($externalId, $provider);

        return new MangaLookupResult(
            manga: $result['manga'],
            fromCache: false,
            provider: $result['provider'],
            synced: true,
        );
    }

    /**
     * Busca os detalhes de uma obra em um provedor específico sem persistir no
     * banco local. Usado na página de detalhes da seção "Descobrir".
     */
    public function findExternal(string $externalId, ProviderName $provider): ?ExternalManga
    {
        if (! $this->resolver->isEnabled($provider)) {
            throw new YomiSyncException("Provedor {$provider->value} não está habilitado");
        }

        try {
            return $this->resolver->resolve($provider)->findById($externalId);
        } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
            throw new YomiSyncException($exception->getMessage(), previous: $exception);
        }
    }

    public function search(string $query, ?ProviderName $preferred = null): array
    {
        $results = [];

        foreach ($this->orderedProviders($preferred) as $providerName) {
            $startedAt = now();

            try {
                $results[$providerName->value] = $this->resolver->resolve($providerName)->search($query);

                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Busca,
                    status: 'sucesso',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                break;
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Busca,
                    status: 'falha',
                    errorType: $exception->errorType ?? $this->errorType($exception),
                    errorMessage: $exception->getMessage(),
                    httpStatus: $exception->httpStatus ?? null,
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }
        }

        if ($results === []) {
            throw new YomiSyncException('Falha na busca em todos os provedores');
        }

        return array_merge(...array_values($results));
    }

    /**
     * Obras em destaque dos provedores, com fallback, para a seção "Descobrir".
     *
     * @return array<int, ExternalManga>
     */
    public function discover(int $limit = 12, ?ProviderName $preferred = null): array
    {
        foreach ($this->orderedProviders($preferred) as $providerName) {
            $startedAt = now();

            try {
                $results = $this->resolver->resolve($providerName)->topManga($limit);

                if ($results === []) {
                    continue;
                }

                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Descoberta,
                    status: 'sucesso',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                return array_values($results);
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Descoberta,
                    status: 'falha',
                    errorType: $exception->errorType ?? $this->errorType($exception),
                    errorMessage: $exception->getMessage(),
                    httpStatus: $exception->httpStatus ?? null,
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }
        }

        throw new YomiSyncException('Falha na descoberta em todos os provedores');
    }

    /**
     * Obras lançadas recentemente nos provedores, com fallback, para a descoberta.
     *
     * @return array<int, ExternalManga>
     */
    public function recent(int $limit = 12, ?ProviderName $preferred = null): array
    {
        foreach ($this->orderedProviders($preferred) as $providerName) {
            $startedAt = now();

            try {
                $results = $this->resolver->resolve($providerName)->getRecent($limit);

                if ($results === []) {
                    continue;
                }

                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Descoberta,
                    status: 'sucesso',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                return array_values($results);
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Descoberta,
                    status: 'falha',
                    errorType: $exception->errorType ?? $this->errorType($exception),
                    errorMessage: $exception->getMessage(),
                    httpStatus: $exception->httpStatus ?? null,
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }
        }

        throw new YomiSyncException('Falha na listagem de recentes em todos os provedores');
    }

    private function findLocal(?int $mangaId, ?string $provider, ?string $externalId): ?Manga
    {
        if ($mangaId !== null) {
            return Manga::find($mangaId);
        }

        if ($provider !== null && $externalId !== null) {
            return $this->mangaRepository->findByExternalId($provider, $externalId);
        }

        return null;
    }

    /**
     * @return array{manga: Manga, provider: ProviderName}
     */
    private function createFromProviders(?string $externalId, ?string $sourceProvider): array
    {
        foreach ($this->orderedProviders($this->parseProvider($sourceProvider)) as $providerName) {
            $lookupId = $this->lookupIdFor($providerName, $externalId, $sourceProvider);

            if ($lookupId === null) {
                continue;
            }

            $startedAt = now();

            try {
                $external = $this->resolver->resolve($providerName)->findById($lookupId);
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Metadados,
                    status: 'falha',
                    errorType: $exception->errorType ?? $this->errorType($exception),
                    errorMessage: $exception->getMessage(),
                    httpStatus: $exception->httpStatus ?? null,
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }

            if ($external === null) {
                $this->syncLogRepository->record(
                    manga: null,
                    provider: $providerName,
                    operation: SyncOperation::Metadados,
                    status: 'nao_encontrado',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }

            $manga = $this->mangaService->createFromExternal($external, $providerName);

            $this->syncChaptersBestEffort($manga, $providerName, $lookupId);

            $this->syncLogRepository->record(
                manga: $manga,
                provider: $providerName,
                operation: SyncOperation::Metadados,
                status: 'sucesso',
                startedAt: $startedAt,
                finishedAt: now(),
            );

            $this->mediaService->enqueueMediaFor($manga, $external);

            $this->mangaRepository->markSynced($manga, (int) config('yomi.sync.next_sync_after_minutes', 1440));

            return ['manga' => $manga->fresh(), 'provider' => $providerName];
        }

        throw new YomiSyncException(
            $externalId !== null
                ? 'Nenhum provedor conseguiu recuperar a obra'
                : 'Nenhum identificador externo informado para cadastrar a obra',
        );
    }

    private function lookupIdFor(ProviderName $providerName, ?string $externalId, ?string $sourceProvider): ?string
    {
        if ($sourceProvider === null || $externalId === null) {
            return $externalId;
        }

        if ($providerName->value === $sourceProvider) {
            return $externalId;
        }

        // Ao cair no AniList com um id do Jikan/MAL, usa-se o filtro idMal.
        if ($providerName === ProviderName::AniList && $sourceProvider === ProviderName::Jikan->value) {
            return 'mal:'.$externalId;
        }

        return null;
    }

    /**
     * @return array<int, ProviderName>
     */
    private function orderedProviders(?ProviderName $preferred): array
    {
        $priority = $this->resolver->priority();

        if ($preferred === null || ! in_array($preferred, $priority, true)) {
            return $priority;
        }

        $rest = array_values(array_filter(
            $priority,
            fn (ProviderName $provider): bool => $provider !== $preferred,
        ));

        return array_values([$preferred, ...$rest]);
    }

    private function parseProvider(?string $provider): ?ProviderName
    {
        if ($provider === null) {
            return null;
        }

        return ProviderName::tryFrom($provider);
    }

    private function syncChaptersBestEffort(Manga $manga, ProviderName $providerName, string $lookupId): void
    {
        try {
            $chapters = $this->resolver->resolve($providerName)->getChapters($lookupId);
            $this->mangaSyncService->persistChapters($manga, $chapters, $providerName);
        } catch (Throwable) {
            // Auxiliar: falha não bloqueia o cadastro.
        }
    }

    private function errorType(Throwable $throwable): string
    {
        return (new \ReflectionClass($throwable))->getShortName();
    }
}

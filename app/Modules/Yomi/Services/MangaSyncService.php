<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\SyncOperation;
use App\Modules\Yomi\Exceptions\ProviderMalformedResponseException;
use App\Modules\Yomi\Exceptions\ProviderUnavailableException;
use App\Modules\Yomi\Exceptions\YomiSyncException;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Providers\ProviderResolver;
use App\Modules\Yomi\Repositories\CapituloRepository;
use App\Modules\Yomi\Repositories\MangaRepository;
use App\Modules\Yomi\Repositories\SyncLogRepository;
use Throwable;

class MangaSyncService
{
    public function __construct(
        protected MangaRepository $mangaRepository,
        protected MangaService $mangaService,
        protected ProviderResolver $resolver,
        protected SyncLogRepository $syncLogRepository,
        protected CapituloRepository $capituloRepository,
        protected MediaService $mediaService,
    ) {}

    public function sync(int $mangaId, ?ProviderName $preferred = null): Manga
    {
        $manga = Manga::findOrFail($mangaId);

        if ($manga->sync_status === 'sincronizando') {
            return $manga;
        }

        $manga = $this->mangaRepository->markSyncing($manga);

        foreach ($this->orderedProviders($preferred) as $providerName) {
            $externalId = $manga->getExternalId($providerName->value);

            if ($externalId === null) {
                continue;
            }

            $startedAt = now();

            try {
                $external = $this->resolver->resolve($providerName)->findById($externalId);
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: $manga,
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
                    manga: $manga,
                    provider: $providerName,
                    operation: SyncOperation::Metadados,
                    status: 'nao_encontrado',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                continue;
            }

            $manga = $this->mangaService->updateFromExternal($manga, $external, $providerName);

            $this->syncChaptersBestEffort($manga, $providerName, $externalId);

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

            return $manga->fresh();
        }

        $this->mangaRepository->markFailed($manga);

        throw new YomiSyncException('Sincronização falhou para todos os provedores disponíveis');
    }

    public function syncChapterData(int $mangaId, ?ProviderName $preferred = null): int
    {
        $manga = Manga::findOrFail($mangaId);

        foreach ($this->orderedProviders($preferred) as $providerName) {
            $externalId = $manga->getExternalId($providerName->value);

            if ($externalId === null) {
                continue;
            }

            $startedAt = now();

            try {
                $chapters = $this->resolver->resolve($providerName)->getChapters($externalId);

                $this->syncLogRepository->record(
                    manga: $manga,
                    provider: $providerName,
                    operation: SyncOperation::Capitulos,
                    status: 'sucesso',
                    startedAt: $startedAt,
                    finishedAt: now(),
                );

                return $this->capituloRepository->syncChapters($manga, $chapters, $providerName->value);
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $this->syncLogRepository->record(
                    manga: $manga,
                    provider: $providerName,
                    operation: SyncOperation::Capitulos,
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

        throw new YomiSyncException('Falha ao sincronizar capítulos em todos os provedores');
    }

    /**
     * @param  array<int, \App\Modules\Yomi\DTOs\ExternalChapter>  $chapters
     */
    public function persistChapters(Manga $manga, array $chapters, ProviderName $providerName): int
    {
        return $this->capituloRepository->syncChapters($manga, $chapters, $providerName->value);
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

    private function syncChaptersBestEffort(Manga $manga, ProviderName $providerName, string $externalId): void
    {
        try {
            $chapters = $this->resolver->resolve($providerName)->getChapters($externalId);

            $this->capituloRepository->syncChapters($manga, $chapters, $providerName->value);
        } catch (Throwable) {
            // Capítulos são auxiliares: a falha não deve interromper a sincronização principal.
        }
    }

    private function errorType(Throwable $throwable): string
    {
        return (new \ReflectionClass($throwable))->getShortName();
    }
}

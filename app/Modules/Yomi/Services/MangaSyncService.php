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
        $lastException = null;

        foreach ($this->orderedProviders($preferred) as $providerName) {
            $externalId = $manga->getExternalId($providerName->value);

            if ($externalId === null) {
                continue;
            }

            $startedAt = now();

            try {
                $chapters = $this->resolver->resolve($providerName)->getChapters($externalId);

                if (! empty($chapters)) {
                    $this->syncLogRepository->record(
                        manga: $manga,
                        provider: $providerName,
                        operation: SyncOperation::Capitulos,
                        status: 'sucesso',
                        startedAt: $startedAt,
                        finishedAt: now(),
                    );

                    return $this->capituloRepository->syncChapters($manga, $chapters, $providerName->value);
                }
            } catch (ProviderUnavailableException|ProviderMalformedResponseException $exception) {
                $lastException = $exception;

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

        // Tenta buscar feed de capítulos no MangaDex
        if ($manga->getExternalId(ProviderName::MangaDex->value) === null) {
            $candidateTitles = array_values(array_unique(array_filter([
                $manga->titulo,
                $manga->titulo_original,
                ...$manga->titulos()->pluck('titulo')->all(),
            ])));

            foreach ($candidateTitles as $candidateTitle) {
                try {
                    $dexResults = $this->resolver->resolve(ProviderName::MangaDex)->search($candidateTitle, 1);
                    if (! empty($dexResults)) {
                        $first = $dexResults[0];
                        $dexId = $first->externalIds[0]['external_id'] ?? null;
                        if ($dexId !== null) {
                            $manga->externalIds()->firstOrCreate(
                                ['provider' => ProviderName::MangaDex->value],
                                ['external_id' => $dexId],
                            );
                            break;
                        }
                    }
                } catch (Throwable) {
                    // Tenta próximo título
                }
            }
        }

        $dexId = $manga->getExternalId(ProviderName::MangaDex->value);
        if ($dexId !== null) {
            try {
                $dexChapters = $this->resolver->resolve(ProviderName::MangaDex)->getChapters($dexId);
                if (! empty($dexChapters)) {
                    $this->syncLogRepository->record(
                        manga: $manga,
                        provider: ProviderName::MangaDex,
                        operation: SyncOperation::Capitulos,
                        status: 'sucesso',
                        startedAt: now(),
                        finishedAt: now(),
                    );

                    return $this->capituloRepository->syncChapters($manga, $dexChapters, ProviderName::MangaDex->value);
                }
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        // Tenta buscar capítulos no Kitsu se MangaDex não resolveu
        if ($manga->getExternalId(ProviderName::Kitsu->value) === null) {
            $candidateTitles = array_values(array_unique(array_filter([
                $manga->titulo,
                $manga->titulo_original,
                ...$manga->titulos()->pluck('titulo')->all(),
            ])));

            foreach ($candidateTitles as $candidateTitle) {
                try {
                    $kitsuResults = $this->resolver->resolve(ProviderName::Kitsu)->search($candidateTitle, 1);
                    if (! empty($kitsuResults)) {
                        $firstKitsu = $kitsuResults[0];
                        $kitsuId = $firstKitsu->externalIds[0]['external_id'] ?? null;
                        if ($kitsuId !== null) {
                            $manga->externalIds()->firstOrCreate(
                                ['provider' => ProviderName::Kitsu->value],
                                ['external_id' => $kitsuId],
                            );
                            if ($firstKitsu->chapters !== null && $firstKitsu->chapters > ($manga->capitulos_conhecidos ?? 0)) {
                                $manga->update(['capitulos_conhecidos' => $firstKitsu->chapters]);
                            }
                            break;
                        }
                    }
                } catch (Throwable) {
                    // Tenta próximo título
                }
            }
        }

        $kitsuId = $manga->getExternalId(ProviderName::Kitsu->value);
        if ($kitsuId !== null) {
            try {
                $kitsuChapters = $this->resolver->resolve(ProviderName::Kitsu)->getChapters($kitsuId);
                if (! empty($kitsuChapters)) {
                    $this->syncLogRepository->record(
                        manga: $manga,
                        provider: ProviderName::Kitsu,
                        operation: SyncOperation::Capitulos,
                        status: 'sucesso',
                        startedAt: now(),
                        finishedAt: now(),
                    );

                    return $this->capituloRepository->syncChapters($manga, $kitsuChapters, ProviderName::Kitsu->value);
                }
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        // Se a obra não tem capítulos conhecidos definidos, tenta atualizar metadados
        if (($manga->capitulos_conhecidos ?? 0) <= 0) {
            try {
                $manga = $this->sync($manga->id);
            } catch (Throwable) {
                // Sincronização de metadados não bloqueia
            }
        }

        // Fallback: se a contagem de capítulos é conhecida, gera os capítulos com numeração padrão
        if (($manga->capitulos_conhecidos ?? 0) > 0) {
            $generated = $this->capituloRepository->generateKnownChapters($manga);
            if ($generated > 0 || $manga->capitulos()->exists()) {
                return $generated;
            }
        }

        if ($lastException !== null) {
            throw new YomiSyncException('Falha ao sincronizar capítulos: '.$lastException->getMessage(), previous: $lastException);
        }

        return 0;
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
            $this->syncChapterData($manga->id, $providerName);
        } catch (Throwable) {
            // Capítulos são auxiliares: a falha não deve interromper a sincronização principal.
            if (($manga->capitulos_conhecidos ?? 0) > 0 && $manga->capitulos()->count() === 0) {
                $this->capituloRepository->generateKnownChapters($manga, $providerName->value);
            }
        }
    }

    private function errorType(Throwable $throwable): string
    {
        return (new \ReflectionClass($throwable))->getShortName();
    }
}

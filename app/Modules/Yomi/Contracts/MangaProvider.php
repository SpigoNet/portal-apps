<?php

namespace App\Modules\Yomi\Contracts;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\DTOs\ExternalManga;

interface MangaProvider
{
    public function findById(string $id): ?ExternalManga;

    /**
     * @return array<int, ExternalManga>
     */
    public function search(string $query): array;

    /**
     * @return array<int, ExternalChapter>
     */
    public function getChapters(string $id): array;

    /**
     * Lista obras em destaque/populares para a descoberta.
     *
     * @return array<int, ExternalManga>
     */
    public function topManga(int $limit = 12): array;

    /**
     * Lista obras lançadas/publicadas recentemente.
     *
     * @return array<int, ExternalManga>
     */
    public function getRecent(int $limit = 12): array;

    /**
     * Verifica o estado de saúde/connectividade da API.
     *
     * @return array{provider: string, status: string, latency_ms: int|null, message: string|null}
     */
    public function health(): array;
}

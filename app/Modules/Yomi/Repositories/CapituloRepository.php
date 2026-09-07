<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\DTOs\ExternalChapter;
use App\Modules\Yomi\Models\Capitulo;
use App\Modules\Yomi\Models\Manga;

class CapituloRepository
{
    /**
     * Sincroniza a lista de capítulos conhecidos de uma obra, sem remover os existentes.
     *
     * @param  array<int, ExternalChapter>  $externalChapters
     */
    public function syncChapters(Manga $manga, array $externalChapters, string $provider): int
    {
        $saved = 0;

        foreach ($externalChapters as $externalChapter) {
            $exists = Capitulo::query()
                ->where('manga_id', $manga->id)
                ->where('numero', $externalChapter->number)
                ->exists();

            if ($exists) {
                continue;
            }

            Capitulo::create([
                'manga_id' => $manga->id,
                'numero' => $externalChapter->number,
                'titulo' => $externalChapter->title,
                'external_id' => $externalChapter->externalId,
                'provider' => $provider,
                'data_publicacao' => $externalChapter->publishedAt,
                'last_synced_at' => now(),
            ]);

            $saved++;
        }

        return $saved;
    }
}

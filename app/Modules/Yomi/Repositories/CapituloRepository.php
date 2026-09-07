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
        $count = 0;

        foreach ($externalChapters as $externalChapter) {
            if ($externalChapter->number === null) {
                continue;
            }

            $capitulo = Capitulo::query()
                ->where('manga_id', $manga->id)
                ->where('numero', $externalChapter->number)
                ->first();

            if ($capitulo !== null) {
                $updates = [];
                if ($externalChapter->title !== null
                    && $externalChapter->title !== ''
                    && $capitulo->titulo !== $externalChapter->title) {
                    $updates['titulo'] = $externalChapter->title;
                }
                if ($externalChapter->externalId !== null && $capitulo->external_id !== $externalChapter->externalId) {
                    $updates['external_id'] = $externalChapter->externalId;
                }
                if ($externalChapter->publishedAt !== null && ! $capitulo->data_publicacao?->equalTo($externalChapter->publishedAt)) {
                    $updates['data_publicacao'] = $externalChapter->publishedAt;
                }
                $updates['last_synced_at'] = now();

                $capitulo->update($updates);
                $count += count($updates) > 1 ? 1 : 0;

                continue;
            }

            Capitulo::create([
                'manga_id' => $manga->id,
                'numero' => $externalChapter->number,
                'titulo' => $externalChapter->title ?: ('Capítulo '.$externalChapter->number),
                'external_id' => $externalChapter->externalId,
                'provider' => $provider,
                'data_publicacao' => $externalChapter->publishedAt,
                'last_synced_at' => now(),
            ]);

            $count++;
        }

        $totalChapters = $manga->capitulos()->count();
        if ($totalChapters > ($manga->capitulos_conhecidos ?? 0)) {
            $manga->update(['capitulos_conhecidos' => $totalChapters]);
        }

        return $count;
    }

    /**
     * Gera capítulos com numeração padrão para obras com contagem conhecida
     * quando a API externa não disponibiliza lista granular de capítulos.
     */
    public function generateKnownChapters(Manga $manga, ?string $provider = null): int
    {
        $total = $manga->capitulos_conhecidos;
        if ($total === null || $total <= 0) {
            return 0;
        }

        // Limita a um número razoável para evitar loops gigantes em metadados incorretos
        $total = min($total, 2000);
        $saved = 0;
        $existingNumbers = Capitulo::query()
            ->where('manga_id', $manga->id)
            ->pluck('numero')
            ->map(fn ($n) => (float) $n)
            ->flip();

        for ($i = 1; $i <= $total; $i++) {
            if ($existingNumbers->has((float) $i)) {
                continue;
            }

            Capitulo::create([
                'manga_id' => $manga->id,
                'numero' => (float) $i,
                'titulo' => 'Capítulo '.$i,
                'provider' => $provider ?? $manga->fonte_original ?? 'sistema',
                'last_synced_at' => now(),
            ]);

            $saved++;
        }

        return $saved;
    }
}

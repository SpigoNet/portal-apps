<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Models\Criador;

class CriadorRepository
{
    public function findOrCreate(string $nome, ?string $urlExterna = null, ?string $externalId = null): Criador
    {
        $criador = Criador::firstOrCreate(['nome' => $nome]);

        if (($urlExterna !== null && $criador->url_externa === null)
            || ($externalId !== null && $criador->external_id === null)) {
            $criador->update([
                'url_externa' => $criador->url_externa ?? $urlExterna,
                'external_id' => $criador->external_id ?? $externalId,
            ]);
        }

        return $criador;
    }
}

<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Models\Personagem;

class PersonagemRepository
{
    public function findOrCreate(
        string $nome,
        ?string $nomeOriginal = null,
        ?string $descricao = null,
        ?string $urlExterna = null,
        ?string $externalId = null,
    ): Personagem {
        $personagem = Personagem::firstOrCreate(['nome' => $nome]);

        if (($personagem->nome_original === null && $nomeOriginal !== null)
            || ($personagem->descricao === null && $descricao !== null)
            || ($personagem->url_externa === null && $urlExterna !== null)
            || ($personagem->external_id === null && $externalId !== null)) {
            $personagem->update([
                'nome_original' => $personagem->nome_original ?? $nomeOriginal,
                'descricao' => $personagem->descricao ?? $descricao,
                'url_externa' => $personagem->url_externa ?? $urlExterna,
                'external_id' => $personagem->external_id ?? $externalId,
            ]);
        }

        return $personagem;
    }
}

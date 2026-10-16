<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\AiModel;
use App\Modules\MundosDeMim\Models\ParametroImagem;

class ParametrosImagemService
{
    /**
     * Parâmetros pré-configurados do provedor do modelo, prontos para virar `$options` do driver.
     *
     * @return array<string, string|int>
     */
    public function para(?AiModel $modelo): array
    {
        if (! $modelo || ! $modelo->ai_provedor_id) {
            return [];
        }

        $registro = ParametroImagem::where('ai_provedor_id', $modelo->ai_provedor_id)->first();

        return ParametroImagem::sanitizar($registro?->parametros ?? []);
    }

    /**
     * Mescla os padrões do provedor com as opções da chamada (a chamada prevalece).
     *
     * @param  array<string, mixed>  $opcoes
     * @return array<string, mixed>
     */
    public function aplicar(?AiModel $modelo, array $opcoes = []): array
    {
        return array_merge($this->para($modelo), $opcoes);
    }
}

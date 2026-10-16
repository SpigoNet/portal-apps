<?php

namespace App\Modules\Cardapio\Services\Assistente;

use App\Models\User;
use App\Modules\Admin\Services\AiProviderService;
use App\Services\AI\AiDriverInterface;

/**
 * Resolve o driver de texto configurado no Admin (modelo do usuário ou padrão do portal).
 * Separado para poder ser trocado por um driver falso nos testes.
 */
class ProvedorIA
{
    public function driver(?User $user): ?AiDriverInterface
    {
        $servico = new AiProviderService;

        return $servico->createTextDriver($servico->getTextToTextProvider($user));
    }
}

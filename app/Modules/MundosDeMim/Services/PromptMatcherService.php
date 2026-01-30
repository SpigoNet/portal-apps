<?php

namespace App\Modules\MundosDeMim\Services;

use App\Modules\MundosDeMim\Models\Prompt;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Modules\MundosDeMim\Models\RelatedPerson;
use Illuminate\Support\Collection;

class PromptMatcherService
{
    /**
     * Filtra uma lista de prompts, retornando apenas aqueles que o usuário
     * possui os requisitos para atender.
     */
    public function filterValidPromptsForUser(Collection $prompts, int $userId): Collection
    {
        // Carrega dados do usuário antecipadamente para evitar queries em loop
        $userAttributes = UserAttribute::where('user_id', $userId)->first();
        $relatedPeople = RelatedPerson::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        return $prompts->filter(function ($prompt) use ($userAttributes, $relatedPeople) {
            // Se o prompt não tem requisitos, ele é válido para todos
            if ($prompt->requirements->isEmpty()) {
                return true;
            }

            // Verifica CADA requisito do prompt
            foreach ($prompt->requirements as $req) {
                if (!$this->checkRequirement($req, $userAttributes, $relatedPeople)) {
                    return false; // Falhou em um requisito, prompt descartado
                }
            }

            return true;
        });
    }

    private function checkRequirement($req, $userAttributes, $relatedPeople): bool
    {
        switch ($req->requirement_key) {

            // Verificação: Usuário tem um tipo específico de pessoa ativa?
            // Ex: key='has_relationship', value='Pet'
            case 'has_relationship':
                return $relatedPeople->contains('relationship', $req->requirement_value);

            // Verificação: Altura mínima (exemplo de verificação biométrica)
            // Ex: key='min_height', value='160'
            case 'min_height':
                if (!$userAttributes || !$userAttributes->height) return false;
                return $userAttributes->height >= (float) $req->requirement_value;

            // Verificação: Tipo de corpo específico
            // Ex: key='body_type', value='atletico'
            case 'body_type':
                if (!$userAttributes) return false;
                return strtolower($userAttributes->body_type) === strtolower($req->requirement_value);

            // Adicione outras verificações conforme necessidade...

            default:
                // Se o sistema não conhece a regra, por segurança retorna falso
                return false;
        }
    }
}

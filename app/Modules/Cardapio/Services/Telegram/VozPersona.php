<?php

namespace App\Modules\Cardapio\Services\Telegram;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;

/**
 * Dá a voz da persona a uma frase curta (abertura/fechamento). Os dados (receitas, quantidades, validades)
 * nunca passam pela IA: ela só reescreve o texto de apresentação e, se falhar, fica a frase original.
 */
class VozPersona
{
    public function __construct(private readonly InterpretadorPersonaService $interpretador) {}

    public function falar(Persona $persona, string $frase): string
    {
        $falada = trim($this->interpretador->interpretar($persona, $frase));

        // Frase de apresentação é curta; resposta enorme ou com quebras é a IA divagando.
        return ($falada === '' || mb_strlen($falada) > 280 || str_contains($falada, "\n")) ? $frase : $falada;
    }

    /**
     * A persona escreve a abertura da mensagem a partir dos fatos (sem repetir listas). Devolve o texto escapado
     * para HTML, ou null se a IA falhar ou divagar demais; o chamador usa então uma frase fixa.
     */
    public function abrir(Persona $persona, string $fatos): ?string
    {
        $texto = trim($this->interpretador->narrar($persona, $fatos));

        return ($texto === '' || mb_strlen($texto) > 900) ? null : e($texto);
    }
}

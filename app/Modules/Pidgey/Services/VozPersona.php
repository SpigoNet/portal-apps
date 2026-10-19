<?php

namespace App\Modules\Pidgey\Services;

use App\Modules\Alfred\Models\Persona;

/**
 * Dá a voz da persona à abertura de uma mensagem de módulo. Os dados (valores, prazos, tarefas) nunca passam
 * pela IA: ela só escreve o texto de apresentação e, se falhar, o chamador usa uma frase fixa.
 */
class VozPersona
{
    public function __construct(private readonly InterpretadorPersonaService $interpretador) {}

    /**
     * A persona escreve a abertura a partir dos fatos. Devolve o texto escapado para HTML, ou null se a IA
     * falhar ou divagar demais.
     */
    public function abrir(Persona $persona, string $fatos, ?string $instrucao = null): ?string
    {
        $texto = trim($this->interpretador->narrar($persona, $fatos, null, $instrucao));

        return ($texto === '' || mb_strlen($texto) > 900) ? null : e($texto);
    }
}

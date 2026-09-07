<?php

namespace App\Modules\Yomi\Enums;

enum MidiaStatus: string
{
    case Pendente = 'pendente';
    case Baixando = 'baixando';
    case Baixada = 'baixada';
    case Falha = 'falha';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Baixando => 'Baixando',
            self::Baixada => 'Baixada',
            self::Falha => 'Falha',
        };
    }
}

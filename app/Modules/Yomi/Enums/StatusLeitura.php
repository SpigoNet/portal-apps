<?php

namespace App\Modules\Yomi\Enums;

enum StatusLeitura: string
{
    case Lendo = 'lendo';
    case Concluido = 'concluido';
    case PretendoLer = 'pretendo_ler';
    case Pausado = 'pausado';
    case Abandonado = 'abandonado';

    public function label(): string
    {
        return match ($this) {
            self::Lendo => 'Lendo',
            self::Concluido => 'Lido/Concluído',
            self::PretendoLer => 'Pretendo Ler',
            self::Pausado => 'Pausado',
            self::Abandonado => 'Abandonado',
        };
    }
}

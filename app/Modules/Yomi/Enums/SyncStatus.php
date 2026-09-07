<?php

namespace App\Modules\Yomi\Enums;

enum SyncStatus: string
{
    case Pendente = 'pendente';
    case Sincronizando = 'sincronizando';
    case Sincronizado = 'sincronizado';
    case Falha = 'falha';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Sincronizando => 'Sincronizando',
            self::Sincronizado => 'Sincronizado',
            self::Falha => 'Falha',
        };
    }
}

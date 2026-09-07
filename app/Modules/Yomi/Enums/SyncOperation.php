<?php

namespace App\Modules\Yomi\Enums;

enum SyncOperation: string
{
    case Metadados = 'metadados';
    case Capitulos = 'capitulos';
    case Busca = 'busca';
    case Descoberta = 'descoberta';
    case Midia = 'midia';
}

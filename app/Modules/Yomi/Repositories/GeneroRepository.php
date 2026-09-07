<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Models\Genero;

class GeneroRepository
{
    public function findOrCreate(string $nome): Genero
    {
        return Genero::firstOrCreate(['nome' => $nome]);
    }
}

<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    public const TIPOS = [
        'fora' => 'Refeição fora de casa (não planejar)',
        'convidados' => 'Convidados (mais pessoas à mesa)',
    ];

    protected $table = 'cardapio_eventos';

    protected $fillable = ['casa_id', 'data', 'tipo_refeicao', 'tipo', 'titulo', 'pessoas_extras'];

    protected function casts(): array
    {
        return ['data' => 'date:Y-m-d'];
    }
}

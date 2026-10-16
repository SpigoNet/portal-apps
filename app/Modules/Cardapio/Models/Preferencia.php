<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preferencia extends Model
{
    public const NIVEIS = [
        5 => 'Gosta muito (+5)',
        3 => 'Gosta (+3)',
        0 => 'Neutro (0)',
        -5 => 'Não gosta (−5)',
        -10 => 'Odeia (−10)',
    ];

    protected $table = 'cardapio_preferencias';

    protected $fillable = ['pessoa_id', 'ingrediente_id', 'nivel'];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }
}

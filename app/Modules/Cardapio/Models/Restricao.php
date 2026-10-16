<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restricao extends Model
{
    public const TIPOS = [
        'alergia' => 'Alergia (nunca usar)',
        'intolerancia' => 'Intolerância (evitar ou substituir)',
        'preferencia' => 'Preferência (prefere evitar)',
        'objetivo' => 'Objetivo (ex.: reduzir lactose)',
    ];

    protected $table = 'cardapio_restricoes';

    protected $fillable = ['pessoa_id', 'ingrediente_id', 'tipo', 'descricao'];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }
}

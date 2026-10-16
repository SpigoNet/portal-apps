<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubstituicaoIngrediente extends Model
{
    protected $table = 'cardapio_substituicoes_ingrediente';

    protected $fillable = ['casa_id', 'ingrediente_id', 'substituto_id', 'compatibilidade'];

    public function substituto(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'substituto_id');
    }
}

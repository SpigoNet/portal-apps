<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceitaIngrediente extends Model
{
    protected $table = 'cardapio_receita_ingredientes';

    protected $fillable = ['receita_id', 'ingrediente_id', 'quantidade', 'opcional'];

    protected function casts(): array
    {
        return ['quantidade' => 'float', 'opcional' => 'boolean'];
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }
}

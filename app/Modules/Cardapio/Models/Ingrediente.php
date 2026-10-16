<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingrediente extends Model
{
    protected $table = 'cardapio_ingredientes';

    protected $fillable = ['casa_id', 'categoria_id', 'nome', 'unidade_base', 'sobra'];

    protected function casts(): array
    {
        return ['sobra' => 'boolean'];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaIngrediente::class, 'categoria_id');
    }

    public function substituicoes(): HasMany
    {
        return $this->hasMany(SubstituicaoIngrediente::class, 'ingrediente_id');
    }
}

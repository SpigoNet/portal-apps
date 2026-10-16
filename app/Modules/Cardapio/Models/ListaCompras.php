<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListaCompras extends Model
{
    protected $table = 'cardapio_listas_compras';

    protected $fillable = ['casa_id', 'plano_id', 'titulo', 'status'];

    public function casa(): BelongsTo
    {
        return $this->belongsTo(Casa::class, 'casa_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoSemanal::class, 'plano_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemCompra::class, 'lista_id');
    }
}

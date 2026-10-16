<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCompra extends Model
{
    protected $table = 'cardapio_itens_compra';

    protected $fillable = [
        'lista_id', 'ingrediente_id', 'descricao', 'quantidade_necessaria', 'quantidade_estoque',
        'quantidade_comprar', 'comprado', 'manual', 'adicionado_ao_estoque',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_necessaria' => 'float',
            'quantidade_estoque' => 'float',
            'quantidade_comprar' => 'float',
            'comprado' => 'boolean',
            'manual' => 'boolean',
            'adicionado_ao_estoque' => 'boolean',
        ];
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaCompras::class, 'lista_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }

    public function nome(): string
    {
        return $this->ingrediente?->nome ?? (string) $this->descricao;
    }
}

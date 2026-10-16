<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoRecorrente extends Model
{
    protected $table = 'cardapio_produtos_recorrentes';

    protected $fillable = ['casa_id', 'ingrediente_id', 'descricao', 'quantidade', 'ativo'];

    protected function casts(): array
    {
        return ['quantidade' => 'float', 'ativo' => 'boolean'];
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

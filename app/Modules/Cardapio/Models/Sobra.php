<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sobra extends Model
{
    protected $table = 'cardapio_sobras';

    protected $fillable = ['casa_id', 'ingrediente_id', 'receita_id', 'refeicao_planejada_id', 'quantidade', 'validade'];

    protected function casts(): array
    {
        return ['quantidade' => 'float', 'validade' => 'date:Y-m-d'];
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id');
    }
}

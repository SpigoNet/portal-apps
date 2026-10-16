<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ausencia extends Model
{
    protected $table = 'cardapio_ausencias';

    protected $fillable = ['casa_id', 'pessoa_id', 'inicio', 'fim', 'motivo'];

    protected function casts(): array
    {
        return ['inicio' => 'date:Y-m-d', 'fim' => 'date:Y-m-d'];
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }
}

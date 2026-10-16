<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item extra de uma refeição (ex.: fruta e bebida da lancheira, além do sanduíche).
 */
class RefeicaoItem extends Model
{
    protected $table = 'cardapio_refeicao_itens';

    protected $fillable = ['refeicao_planejada_id', 'receita_id', 'substituicoes'];

    protected function casts(): array
    {
        return ['substituicoes' => 'array'];
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id');
    }
}

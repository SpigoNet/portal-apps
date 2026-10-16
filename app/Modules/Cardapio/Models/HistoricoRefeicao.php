<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoRefeicao extends Model
{
    /** Feedback => valor de −1 a 1 usado pelo motor. */
    public const FEEDBACKS = [
        'gostamos_muito' => ['Gostamos muito', 1.0],
        'gostamos' => ['Gostamos', 0.5],
        'normal' => ['Normal', 0.0],
        'nao_gostamos' => ['Não gostamos', -0.5],
        'nao_fazer_novamente' => ['Não fazer novamente', -1.0],
    ];

    protected $table = 'cardapio_historico_refeicoes';

    protected $fillable = [
        'casa_id', 'refeicao_planejada_id', 'receita_id', 'data', 'tipo_refeicao', 'qtd_pessoas',
        'consumida_em', 'nota', 'feedback', 'consumo',
    ];

    protected function casts(): array
    {
        return ['data' => 'date:Y-m-d', 'consumida_em' => 'datetime', 'consumo' => 'array'];
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id');
    }

    /**
     * Quanto a família gostou, de −1 a 1: o feedback, ou a nota (1–5) quando não há feedback.
     */
    public function valor(): ?float
    {
        if ($this->feedback !== null && isset(self::FEEDBACKS[$this->feedback])) {
            return self::FEEDBACKS[$this->feedback][1];
        }

        return $this->nota !== null ? ($this->nota - 3) / 2 : null;
    }
}

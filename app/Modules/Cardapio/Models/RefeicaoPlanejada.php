<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefeicaoPlanejada extends Model
{
    protected $table = 'cardapio_refeicoes_planejadas';

    protected $fillable = ['plano_id', 'data', 'tipo_refeicao', 'receita_id', 'qtd_pessoas', 'pessoas_extras', 'pessoas_manual', 'justificativa', 'origem', 'substituicoes', 'consumida_em'];

    protected function casts(): array
    {
        return ['data' => 'date:Y-m-d', 'consumida_em' => 'datetime', 'substituicoes' => 'array', 'pessoas_manual' => 'boolean'];
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoSemanal::class, 'plano_id');
    }

    public function receita(): BelongsTo
    {
        return $this->belongsTo(Receita::class, 'receita_id');
    }

    public function pessoas(): BelongsToMany
    {
        return $this->belongsToMany(Pessoa::class, 'cardapio_refeicao_planejada_pessoa', 'refeicao_planejada_id', 'pessoa_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(RefeicaoItem::class, 'refeicao_planejada_id');
    }

    public function consumida(): bool
    {
        return $this->consumida_em !== null;
    }

    /**
     * Quantidades (unidade base) que esta refeição consome, já com as substituições aplicadas.
     *
     * @return array<int, float> ingrediente_id => quantidade
     */
    public function necessidades(bool $incluirOpcionais = false): array
    {
        $porcoes = max(1, $this->qtd_pessoas);
        $total = $this->receita?->necessidades($porcoes, $incluirOpcionais, $this->substituicoes ?? []) ?? [];

        foreach ($this->itens as $item) {
            foreach ($item->receita?->necessidades($porcoes, $incluirOpcionais, $item->substituicoes ?? []) ?? [] as $id => $quantidade) {
                $total[$id] = ($total[$id] ?? 0) + $quantidade;
            }
        }

        return $total;
    }
}

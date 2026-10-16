<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receita extends Model
{
    public const DIFICULDADES = ['facil' => 'Fácil', 'media' => 'Média', 'dificil' => 'Difícil'];

    public const FREQUENCIAS = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];

    protected $table = 'cardapio_receitas';

    protected $fillable = [
        'casa_id', 'nome', 'descricao', 'rendimento', 'tempo_preparo', 'dificuldade', 'modo_preparo',
        'categorias', 'refeicoes_compativeis', 'favorita', 'frequencia', 'escolhas', 'rejeicoes',
    ];

    protected function casts(): array
    {
        return [
            'categorias' => 'array',
            'refeicoes_compativeis' => 'array',
            'favorita' => 'boolean',
        ];
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoRefeicao::class, 'receita_id');
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(ReceitaIngrediente::class, 'receita_id');
    }

    /**
     * Quantidades necessárias (unidade base) para o número de porções, por ingrediente.
     * Opcionais ficam de fora, a menos que pedidos.
     *
     * @param  array<int, int>  $substituicoes  ingrediente_id => substituto_id (mesma unidade base)
     * @return array<int, float> ingrediente_id => quantidade
     */
    public function necessidades(float $porcoes, bool $incluirOpcionais = false, array $substituicoes = []): array
    {
        $fator = $porcoes / max(1, $this->rendimento);
        $resultado = [];

        foreach ($this->ingredientes as $linha) {
            if ($linha->opcional && ! $incluirOpcionais) {
                continue;
            }

            $id = (int) ($substituicoes[$linha->ingrediente_id] ?? $linha->ingrediente_id);
            $resultado[$id] = ($resultado[$id] ?? 0) + $linha->quantidade * $fator;
        }

        return $resultado;
    }
}

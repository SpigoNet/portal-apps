<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemEstoque extends Model
{
    public const LOCAIS = [
        'despensa' => 'Despensa',
        'geladeira' => 'Geladeira',
        'freezer' => 'Freezer',
        'outro' => 'Outros',
    ];

    public const NORMAL = 'normal';

    public const ATENCAO = 'atencao';

    public const URGENTE = 'urgente';

    public const VENCIDO = 'vencido';

    /** Dias até o vencimento para cair em "urgente" e "atenção". */
    public const DIAS_URGENTE = 3;

    public const DIAS_ATENCAO = 7;

    protected $table = 'cardapio_itens_estoque';

    protected $fillable = [
        'casa_id', 'ingrediente_id', 'local', 'quantidade', 'aproximada', 'validade', 'data_entrada',
        'marca', 'lote', 'quantidade_minima', 'quantidade_ideal', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'float',
            'quantidade_minima' => 'float',
            'quantidade_ideal' => 'float',
            'aproximada' => 'boolean',
            'validade' => 'date:Y-m-d',
            'data_entrada' => 'date:Y-m-d',
        ];
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id');
    }

    /**
     * Dias até a validade (negativo = já venceu); null quando não há validade.
     */
    public function diasParaVencer(): ?int
    {
        return $this->validade === null ? null : (int) today()->diffInDays($this->validade->copy()->startOfDay(), false);
    }

    public function statusValidade(): string
    {
        $dias = $this->diasParaVencer();

        return match (true) {
            $dias === null => self::NORMAL,
            $dias < 0 => self::VENCIDO,
            $dias <= self::DIAS_URGENTE => self::URGENTE,
            $dias <= self::DIAS_ATENCAO => self::ATENCAO,
            default => self::NORMAL,
        };
    }
}

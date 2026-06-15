<?php

namespace App\Modules\Bingo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCartela extends Model
{
    protected $table = 'bingo_cartelas';

    protected $fillable = [
        'jogador_id',
        'numeros',
        'marcacoes',
    ];

    protected $casts = [
        'numeros' => 'array',
        'marcacoes' => 'array',
    ];

    public function jogador(): BelongsTo
    {
        return $this->belongsTo(BingoJogador::class, 'jogador_id');
    }

    public static function gerar(): array
    {
        $numeros = range(1, 25);
        shuffle($numeros);
        $selecionados = array_slice($numeros, 0, 9);

        return array_chunk($selecionados, 3);
    }

    public function todasMarcadas(): bool
    {
        return count($this->marcacoes ?? []) >= 9;
    }

    public function verificarBingo(): bool
    {
        return $this->todasMarcadas();
    }
}

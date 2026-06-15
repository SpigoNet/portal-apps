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

    public function verificarBingo(): bool
    {
        $marcacoes = $this->marcacoes ?? [];
        $marked = [];

        foreach ($marcacoes as $m) {
            $parts = explode('-', (string) $m);
            if (count($parts) === 2) {
                $marked[(int) $parts[0]][(int) $parts[1]] = true;
            }
        }

        for ($r = 0; $r < 3; $r++) {
            $win = true;
            for ($c = 0; $c < 3; $c++) {
                if (! isset($marked[$r][$c])) {
                    $win = false;
                }
            }
            if ($win) {
                return true;
            }
        }

        for ($c = 0; $c < 3; $c++) {
            $win = true;
            for ($r = 0; $r < 3; $r++) {
                if (! isset($marked[$r][$c])) {
                    $win = false;
                }
            }
            if ($win) {
                return true;
            }
        }

        $win = true;
        for ($i = 0; $i < 3; $i++) {
            if (! isset($marked[$i][$i])) {
                $win = false;
            }
        }
        if ($win) {
            return true;
        }

        $win = true;
        for ($i = 0; $i < 3; $i++) {
            if (! isset($marked[$i][2 - $i])) {
                $win = false;
            }
        }

        return $win;
    }
}

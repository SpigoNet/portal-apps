<?php

namespace App\Modules\Bingo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoPartida extends Model
{
    protected $table = 'bingo_partidas';

    protected $fillable = [
        'codigo',
        'tema',
        'status',
        'modo_gestor',
        'dono_token',
        'user_id',
        'numeros_sorteados',
        'mensagens',
    ];

    protected $casts = [
        'modo_gestor' => 'boolean',
        'numeros_sorteados' => 'array',
        'mensagens' => 'array',
    ];

    public function dono(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jogadores(): HasMany
    {
        return $this->hasMany(BingoJogador::class, 'partida_id');
    }

    public function sortearNumero(): ?int
    {
        $sorteados = $this->numeros_sorteados ?? [];
        $disponiveis = array_diff(range(1, 25), $sorteados);

        if (empty($disponiveis)) {
            return null;
        }

        $numero = $disponiveis[array_rand($disponiveis)];
        $sorteados[] = $numero;
        $this->numeros_sorteados = $sorteados;
        $this->save();

        return $numero;
    }
}

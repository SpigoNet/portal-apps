<?php

namespace App\Modules\Bingo\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BingoJogador extends Model
{
    protected $table = 'bingo_jogadores';

    protected $fillable = [
        'partida_id',
        'nome',
        'token',
        'user_id',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(BingoPartida::class, 'partida_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cartela(): HasOne
    {
        return $this->hasOne(BingoCartela::class, 'jogador_id');
    }
}

<?php

namespace App\Modules\Terminal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Melhor tempo de um usuário no desafio diário do Terminal, em um modo (normal ou hard).
 */
class Diario extends Model
{
    protected $table = 'terminal_diarios';

    protected $fillable = ['user_id', 'modo', 'data', 'tempo_ms'];

    protected $casts = [
        'data' => 'date:Y-m-d',
        'tempo_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

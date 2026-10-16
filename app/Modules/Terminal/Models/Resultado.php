<?php

namespace App\Modules\Terminal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Melhor tempo de um usuário em uma fase do Terminal, em um modo (normal ou hard).
 */
class Resultado extends Model
{
    protected $table = 'terminal_resultados';

    protected $fillable = ['user_id', 'modo', 'fase', 'tempo_ms', 'estrelas'];

    protected $casts = [
        'fase' => 'integer',
        'tempo_ms' => 'integer',
        'estrelas' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

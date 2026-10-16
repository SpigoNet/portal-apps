<?php

namespace App\Modules\Terminal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conquista desbloqueada por um usuário no Terminal.
 */
class Conquista extends Model
{
    protected $table = 'terminal_conquistas';

    protected $fillable = ['user_id', 'codigo'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

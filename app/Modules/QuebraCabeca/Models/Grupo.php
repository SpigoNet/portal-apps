<?php

namespace App\Modules\QuebraCabeca\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $table = 'quebra_cabeca_grupos';

    protected $fillable = [
        'user_id',
        'nome',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imagens(): HasMany
    {
        return $this->hasMany(Imagem::class, 'grupo_id');
    }
}

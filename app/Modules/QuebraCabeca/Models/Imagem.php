<?php

namespace App\Modules\QuebraCabeca\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Imagem extends Model
{
    protected $table = 'quebra_cabeca_imagens';

    protected $fillable = [
        'user_id',
        'grupo_id',
        'nome',
        'orientacao',
        'caminho',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}

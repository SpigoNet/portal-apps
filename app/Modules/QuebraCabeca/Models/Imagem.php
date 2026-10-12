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
        'nome',
        'caminho',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Modules\Yomi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioCapitulo extends Model
{
    protected $table = 'yomi_usuario_capitulos';

    protected $fillable = [
        'user_id',
        'manga_id',
        'capitulo_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function capitulo(): BelongsTo
    {
        return $this->belongsTo(Capitulo::class, 'capitulo_id');
    }
}

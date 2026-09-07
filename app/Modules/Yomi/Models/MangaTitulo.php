<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MangaTitulo extends Model
{
    protected $table = 'yomi_manga_titulos';

    protected $fillable = [
        'manga_id',
        'titulo',
        'tipo',
        'idioma',
    ];

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }
}

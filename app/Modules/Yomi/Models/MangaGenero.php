<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MangaGenero extends Model
{
    protected $table = 'yomi_manga_generos';

    protected $fillable = [
        'manga_id',
        'genero_id',
    ];

    public $timestamps = false;

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function genero(): BelongsTo
    {
        return $this->belongsTo(Genero::class, 'genero_id');
    }
}

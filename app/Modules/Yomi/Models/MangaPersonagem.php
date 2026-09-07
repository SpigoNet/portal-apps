<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MangaPersonagem extends Model
{
    protected $table = 'yomi_manga_personagens';

    protected $fillable = [
        'manga_id',
        'personagem_id',
        'papel',
    ];

    public $timestamps = false;

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function personagem(): BelongsTo
    {
        return $this->belongsTo(Personagem::class, 'personagem_id');
    }
}

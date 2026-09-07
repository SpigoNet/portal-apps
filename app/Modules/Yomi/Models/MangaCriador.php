<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MangaCriador extends Model
{
    protected $table = 'yomi_manga_criadores';

    protected $fillable = [
        'manga_id',
        'criador_id',
        'papel',
    ];

    public $timestamps = false;

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Criador::class, 'criador_id');
    }
}

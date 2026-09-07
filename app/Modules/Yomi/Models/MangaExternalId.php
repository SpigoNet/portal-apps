<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MangaExternalId extends Model
{
    protected $table = 'yomi_manga_external_ids';

    protected $fillable = [
        'manga_id',
        'provider',
        'external_id',
    ];

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }
}

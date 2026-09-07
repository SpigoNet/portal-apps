<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genero extends Model
{
    use HasFactory;

    protected $table = 'yomi_generos';

    protected $fillable = [
        'nome',
    ];

    public function mangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'yomi_manga_generos', 'genero_id', 'manga_id');
    }
}

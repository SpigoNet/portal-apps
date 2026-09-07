<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Criador extends Model
{
    use HasFactory;

    protected $table = 'yomi_criadores';

    protected $fillable = [
        'nome',
        'nome_original',
        'url_externa',
        'external_id',
    ];

    public function mangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'yomi_manga_criadores', 'criador_id', 'manga_id')
            ->withPivot('papel');
    }
}

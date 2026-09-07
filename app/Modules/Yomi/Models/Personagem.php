<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Personagem extends Model
{
    use HasFactory;

    protected $table = 'yomi_personagens';

    protected $fillable = [
        'nome',
        'nome_original',
        'descricao',
        'url_externa',
        'external_id',
    ];

    public function mangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'yomi_manga_personagens', 'personagem_id', 'manga_id')
            ->withPivot('papel');
    }
}

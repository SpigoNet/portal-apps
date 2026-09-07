<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Capitulo extends Model
{
    use HasFactory;

    protected $table = 'yomi_capitulos';

    protected $fillable = [
        'manga_id',
        'numero',
        'titulo',
        'external_id',
        'provider',
        'data_publicacao',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'float',
            'data_publicacao' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function usuariosQueLeram(): HasMany
    {
        return $this->hasMany(UsuarioCapitulo::class, 'capitulo_id');
    }
}

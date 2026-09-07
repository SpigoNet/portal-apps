<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Manga extends Model
{
    use HasFactory;

    protected $table = 'yomi_mangas';

    protected $fillable = [
        'titulo',
        'titulo_original',
        'sinopse',
        'status_publicacao',
        'tipo',
        'capitulos_conhecidos',
        'volumes_conhecidos',
        'data_inicio',
        'data_fim',
        'nota_media',
        'classificacao_etaria',
        'fonte_original',
        'last_synced_at',
        'next_sync_at',
        'sync_status',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'capitulos_conhecidos' => 'integer',
            'volumes_conhecidos' => 'integer',
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'nota_media' => 'float',
            'last_synced_at' => 'datetime',
            'next_sync_at' => 'datetime',
            'source_updated_at' => 'datetime',
        ];
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(MangaExternalId::class, 'manga_id');
    }

    public function titulos(): HasMany
    {
        return $this->hasMany(MangaTitulo::class, 'manga_id');
    }

    public function generos(): BelongsToMany
    {
        return $this->belongsToMany(Genero::class, 'yomi_manga_generos', 'manga_id', 'genero_id');
    }

    public function criadores(): BelongsToMany
    {
        return $this->belongsToMany(Criador::class, 'yomi_manga_criadores', 'manga_id', 'criador_id')
            ->withPivot('papel');
    }

    public function personagens(): BelongsToMany
    {
        return $this->belongsToMany(Personagem::class, 'yomi_manga_personagens', 'manga_id', 'personagem_id')
            ->withPivot('papel');
    }

    public function capitulos(): HasMany
    {
        return $this->hasMany(Capitulo::class, 'manga_id');
    }

    public function progressoUsuario(): HasMany
    {
        return $this->hasMany(ProgressoUsuario::class, 'manga_id');
    }

    public function midias(): MorphMany
    {
        return $this->morphMany(Midia::class, 'entity', 'entity_type', 'entity_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'manga_id');
    }

    public function getExternalId(string $provider): ?string
    {
        $record = $this->externalIds()->where('provider', $provider)->first();

        return $record?->external_id;
    }

    public function isStale(int $staleAfterMinutes): bool
    {
        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->lt(now()->subMinutes($staleAfterMinutes));
    }
}

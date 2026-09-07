<?php

namespace App\Modules\Yomi\Models;

use App\Models\User;
use App\Modules\Yomi\Enums\StatusLeitura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressoUsuario extends Model
{
    use HasFactory;

    protected $table = 'yomi_progresso_usuario';

    protected $fillable = [
        'user_id',
        'manga_id',
        'status',
        'ultimo_capitulo_lido',
        'ultimo_volume_lido',
        'nota',
        'favorito',
        'observacoes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_capitulo_lido' => 'float',
            'ultimo_volume_lido' => 'integer',
            'nota' => 'integer',
            'favorito' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => StatusLeitura::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }
}

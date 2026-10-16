<?php

namespace App\Modules\Cardapio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pessoa extends Model
{
    protected $table = 'cardapio_pessoas';

    protected $fillable = ['casa_id', 'user_id', 'nome', 'data_nascimento', 'tipo', 'ativo', 'observacoes'];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date:Y-m-d',
            'ativo' => 'boolean',
        ];
    }

    public function casa(): BelongsTo
    {
        return $this->belongsTo(Casa::class, 'casa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function regrasRotina(): HasMany
    {
        return $this->hasMany(RegraRotina::class, 'pessoa_id');
    }

    public function restricoes(): HasMany
    {
        return $this->hasMany(Restricao::class, 'pessoa_id');
    }

    public function preferencias(): HasMany
    {
        return $this->hasMany(Preferencia::class, 'pessoa_id');
    }

    public function temLogin(): bool
    {
        return $this->user_id !== null;
    }

    public function idade(): ?int
    {
        return $this->data_nascimento?->age;
    }
}

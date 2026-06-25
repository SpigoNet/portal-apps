<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medicamento extends Model
{
    use HasFactory;

    protected $table = 'alfred_medicamentos';

    protected $fillable = [
        'user_id',
        'nome',
        'estoque_atual',
        'ponto_recompra',
    ];

    protected $casts = [
        'estoque_atual' => 'integer',
        'ponto_recompra' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', auth()->id())
            ->first();
    }

    public function scopeBaixoEstoque($query)
    {
        return $query->whereColumn('estoque_atual', '<=', 'ponto_recompra');
    }

    public function scopeEstoqueZero($query)
    {
        return $query->where('estoque_atual', '<=', 0);
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function tomarDose()
    {
        $this->estoque_atual = $this->estoque_atual - 1;
        $this->save();

        return $this->estoque_atual;
    }

    public function getPrecisaComprarAttribute()
    {
        return $this->estoque_atual <= $this->ponto_recompra;
    }

    public function getStatusEstoqueAttribute()
    {
        if ($this->estoque_atual <= 0) {
            return 'critico';
        }
        if ($this->estoque_atual <= $this->ponto_recompra) {
            return 'baixo';
        }

        return 'ok';
    }
}

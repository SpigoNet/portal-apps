<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogDiaRuim extends Model
{
    use HasFactory;

    protected $table = 'alfred_logs_dia_ruim';

    protected $fillable = [
        'user_id',
        'ativado_em',
        'desativado_em',
    ];

    protected $casts = [
        'ativado_em' => 'datetime',
        'desativado_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAtivos($query)
    {
        return $query->whereNull('desativado_em');
    }

    public function scopeEsteMes($query)
    {
        return $query->whereMonth('ativado_em', now()->month)
            ->whereYear('ativado_em', now()->year);
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getDuracaoAttribute()
    {
        if ($this->desativado_em) {
            return $this->ativado_em->diffInHours($this->desativado_em).' horas';
        }

        return 'Ainda ativo';
    }

    public static function contarEsteMes($userId = null)
    {
        $query = self::esteMes();
        if ($userId) {
            $query->doUsuario($userId);
        }

        return $query->count();
    }
}

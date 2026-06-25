<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ConsumoAgua extends Model
{
    use HasFactory;

    protected $table = 'alfred_consumo_agua';

    protected $fillable = [
        'user_id',
        'quantidade_ml',
        'data',
    ];

    protected $casts = [
        'quantidade_ml' => 'integer',
        'data' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data', now()->toDateString());
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function adicionar($quantidade = 250, $userId = null)
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);

        return self::create([
            'user_id' => $userId,
            'quantidade_ml' => $quantidade,
            'data' => now()->toDateString(),
        ]);
    }

    public static function progressoHoje($userId = null)
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);
        $user = $userId ? User::find($userId) : null;
        $meta = $user?->getMetaAguaMl() ?? 2500;
        $consumido = self::hoje()->doUsuario($userId)->sum('quantidade_ml');

        return [
            'consumido' => $consumido,
            'meta' => $meta,
            'percentual' => min(100, round(($consumido / $meta) * 100)),
            'restante' => max(0, $meta - $consumido),
        ];
    }
}

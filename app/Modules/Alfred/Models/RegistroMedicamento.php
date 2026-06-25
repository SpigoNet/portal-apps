<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroMedicamento extends Model
{
    use HasFactory;

    protected $table = 'alfred_registro_medicamentos';

    protected $fillable = [
        'user_id',
        'medicamento_id',
        'data',
        'hora',
        'quantidade',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'hora' => 'datetime:H:i',
        'quantidade' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data', now()->toDateString());
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDoMedicamento($query, $medicamentoId)
    {
        return $query->where('medicamento_id', $medicamentoId);
    }

    public function scopeNaData($query, $data)
    {
        return $query->whereDate('data', $data);
    }

    public static function foiTomadoHoje($medicamentoId, $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return self::where('user_id', $userId)
            ->where('medicamento_id', $medicamentoId)
            ->whereDate('data', now()->toDateString())
            ->exists();
    }

    public static function foiTomadoNaData($medicamentoId, $data, $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return self::where('user_id', $userId)
            ->where('medicamento_id', $medicamentoId)
            ->whereDate('data', $data)
            ->exists();
    }

    public static function registrar($medicamentoId, $quantidade = 1, $data = null, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        $data = $data ?? now()->toDateString();

        return self::create([
            'user_id' => $userId,
            'medicamento_id' => $medicamentoId,
            'data' => $data,
            'hora' => now()->format('H:i:s'),
            'quantidade' => $quantidade,
        ]);
    }

    public static function desfazerRegistroHoje($medicamentoId, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return self::where('user_id', $userId)
            ->where('medicamento_id', $medicamentoId)
            ->whereDate('data', now()->toDateString())
            ->delete();
    }

    public static function desfazerRegistroNaData($medicamentoId, $data, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return self::where('user_id', $userId)
            ->where('medicamento_id', $medicamentoId)
            ->whereDate('data', $data)
            ->delete();
    }

    public static function medicamentosTomadosHoje($userId = null)
    {
        $userId = $userId ?? auth()->id();

        return self::hoje()
            ->doUsuario($userId)
            ->with('medicamento')
            ->get();
    }

    public static function medicamentosTomadosNaData($data, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return self::naData($data)
            ->doUsuario($userId)
            ->with('medicamento')
            ->get();
    }
}

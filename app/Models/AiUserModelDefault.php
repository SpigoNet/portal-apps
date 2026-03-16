<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserModelDefault extends Model
{
    protected $table = 'ai_modelos_padrao_usuario';

    protected $fillable = [
        'user_id',
        'input_type',
        'output_type',
        'ai_modelo_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_modelo_id');
    }

    public static function getPadrao(User $user, string $inputType, string $outputType): ?AiModel
    {
        $padrao = static::with('modelo.provedor')
            ->where('user_id', $user->id)
            ->where('input_type', $inputType)
            ->where('output_type', $outputType)
            ->first();

        return $padrao?->modelo;
    }
}

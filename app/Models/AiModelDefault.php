<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModelDefault extends Model
{
    protected $table = 'ai_modelos_padrao';

    protected $fillable = [
        'input_type',
        'output_type',
        'ai_modelo_id',
    ];

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_modelo_id');
    }

    public function model(): BelongsTo
    {
        return $this->modelo();
    }

    public static function getPadrao(string $inputType, string $outputType): ?AiModel
    {
        $padrao = static::with('modelo.provedor')
            ->where('input_type', $inputType)
            ->where('output_type', $outputType)
            ->first();

        return $padrao?->modelo;
    }
}

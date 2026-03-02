<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'input_type',
        'output_type',
        'description',
        'provider_id',
        'base_url',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public static function inputTypes(): array
    {
        return [
            'text' => 'Texto',
            'image' => 'Imagem',
        ];
    }

    public static function outputTypes(): array
    {
        return [
            'text' => 'Texto',
            'image' => 'Imagem',
            'audio' => 'Áudio',
            'video' => 'Vídeo',
        ];
    }
}

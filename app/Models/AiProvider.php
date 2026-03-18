<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $table = 'ai_provedores';

    protected $fillable = [
        'nome',
        'driver',
        'url_json_modelos',
        'base_url',
        'api_key',
        'default_input_types',
        'default_output_types',
        'is_active',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'default_input_types' => 'array',
        'default_output_types' => 'array',
        'is_active' => 'boolean',
    ];

    public function modelos(): HasMany
    {
        return $this->hasMany(AiModel::class, 'ai_provedor_id');
    }

    public function getNameAttribute(): string
    {
        return $this->attributes['nome'] ?? '';
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value;
    }

    public function getSyncUrlAttribute(): ?string
    {
        return $this->attributes['url_json_modelos'] ?? null;
    }

    public function setSyncUrlAttribute(?string $value): void
    {
        $this->attributes['url_json_modelos'] = $value;
    }

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

    protected static function booted(): void
    {
        static::saving(function (self $provider): void {
            $provider->driver = self::guessDriver(
                $provider->driver,
                $provider->nome,
                $provider->url_json_modelos,
                $provider->base_url
            );
        });
    }

    public static function guessDriver(
        ?string $driver,
        ?string $name = null,
        ?string $syncUrl = null,
        ?string $baseUrl = null
    ): ?string {
        $candidate = strtolower((string) ($driver ?: ''));

        if ($candidate !== '') {
            return match ($candidate) {
                'pollinations image edit',
                'pollination image edit',
                'pollination edit',
                'pollinations_image_edit' => 'pollination_image_edit',
                default => $candidate,
            };
        }

        $haystack = strtolower(trim(implode(' ', array_filter([$name, $syncUrl, $baseUrl]))));

        return match (true) {
            str_contains($haystack, '/v1/images/edits'),
            str_contains($haystack, 'pollination image edit'),
            str_contains($haystack, 'pollinations image edit'),
            str_contains($haystack, 'pollination edit') => 'pollination_image_edit',
            str_contains($haystack, 'pollination') => 'pollination',
            str_contains($haystack, 'airforce') => 'airforce',
            str_contains($haystack, 'kdjingpai') => 'kdjingpai',
            str_contains($haystack, 'gemini') => 'gemini',
            str_contains($haystack, 'lm studio'),
            str_contains($haystack, 'lmstudio'),
            str_contains($haystack, 'localhost:1234') => 'lm_studio',
            default => null,
        };
    }

    public static function getDefault(): ?AiModel
    {
        return AiModelDefault::getPadrao('text', 'text');
    }
}

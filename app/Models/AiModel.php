<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    protected $table = 'ai_modelos';

    protected $fillable = [
        'ai_provedor_id',
        'modelo_id_externo',
        'nome',
        'descricao',
        'input_types',
        'output_types',
        'raw_data',
        'is_active',
        'pricing',
    ];

    protected $casts = [
        'input_types' => 'array',
        'output_types' => 'array',
        'raw_data' => 'array',
        'is_active' => 'boolean',
        'pricing' => 'array',
    ];

    protected function pricing(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $pricing = json_decode($value, true);
                if (! $pricing) {
                    return null;
                }
                foreach ($pricing as $key => $val) {
                    if (is_numeric($val)) {
                        $pricing[$key] = (string) $val;
                    }
                }

                return $pricing;
            },
            set: function ($value) {
                if (is_array($value)) {
                    foreach ($value as $key => $val) {
                        if (is_numeric($val)) {
                            $value[$key] = (string) $val;
                        }
                    }
                }

                return json_encode($value);
            }
        );
    }

    public function provedor(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provedor_id');
    }

    public function gatewayProvider(): BelongsTo
    {
        return $this->provedor();
    }

    public function padroes(): HasMany
    {
        return $this->hasMany(AiModelDefault::class, 'ai_modelo_id');
    }

    public function e_padrao(): HasMany
    {
        return $this->padroes();
    }

    public function getNameAttribute(): string
    {
        return $this->attributes['nome'] ?? '';
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value;
    }

    public function getModelAttribute(): string
    {
        return $this->attributes['modelo_id_externo'] ?? '';
    }

    public function setModelAttribute(?string $value): void
    {
        $this->attributes['modelo_id_externo'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['descricao'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['descricao'] = $value;
    }

    public function getDriverAttribute(): ?string
    {
        return $this->provedor?->driver;
    }

    public function getSupportsImageInputAttribute(): bool
    {
        return in_array('image', $this->input_types ?? [], true);
    }

    public function getSupportsVideoOutputAttribute(): bool
    {
        return in_array('video', $this->output_types ?? [], true);
    }

    public function getSortOrderAttribute(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    public function getPaidOnlyAttribute(): bool
    {
        return (bool) (data_get($this->pricing, 'paid_only')
            ?? data_get($this->raw_data, 'paid_only')
            ?? false);
    }
}

<?php

namespace App\Models;

use App\Modules\MundosDeMim\Models\AIProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewayProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'driver',
        'base_url',
        'sync_url',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AIProvider::class, 'provider_id');
    }
}

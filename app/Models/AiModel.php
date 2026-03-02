<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'provider_id',
        'name',
        'driver',
        'model',
        'description',
        'supports_image_input',
        'supports_video_output',
        'is_default',
        'is_active',
        'sort_order',
        'pricing',
        'paid_only',
    ];

    protected $casts = [
        'supports_image_input' => 'boolean',
        'supports_video_output' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'paid_only' => 'boolean',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }
}

<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\AiGatewayProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIProvider extends Model
{
    protected $table = 'mundos_de_mim_ai_providers';

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

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'mundos_de_mim_default_ai_provider_id');
    }

    public function gatewayProvider(): BelongsTo
    {
        return $this->belongsTo(AiGatewayProvider::class, 'provider_id');
    }

    public function userSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserAiSetting::class, 'ai_provider_id');
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    public static function getImageInputProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('supports_image_input', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getVideoOutputProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('supports_video_output', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function seedDefaultProviders(): void
    {
        $providers = [
            [
                'name' => 'NanoBanana',
                'driver' => 'pollination',
                'model' => 'nanobanana',
                'description' => 'NanoBanana - Gemini 2.5 Flash Image',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'NanoBanana Pro',
                'driver' => 'pollination',
                'model' => 'nanobanana-pro',
                'description' => 'NanoBanana Pro - Gemini 3 Pro Image (4K, Thinking)',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Kontext',
                'driver' => 'pollination',
                'model' => 'kontext',
                'description' => 'FLUX.1 Kontext - In-context editing & generation',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Seedream',
                'driver' => 'pollination',
                'model' => 'seedream',
                'description' => 'Seedream 4.0 - ByteDance ARK (better quality)',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Seedream Pro',
                'driver' => 'pollination',
                'model' => 'seedream-pro',
                'description' => 'Seedream 4.5 Pro - ByteDance ARK (4K, Multi-Image)',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'GPT Image',
                'driver' => 'pollination',
                'model' => 'gptimage',
                'description' => 'GPT Image 1 Mini - OpenAI\'s image generation model',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'GPT Image Large',
                'driver' => 'pollination',
                'model' => 'gptimage-large',
                'description' => 'GPT Image 1.5 - OpenAI\'s advanced image generation model',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'Klein',
                'driver' => 'pollination',
                'model' => 'klein',
                'description' => 'FLUX.2 Klein 4B - Fast image generation & editing on Modal',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 8,
            ],
            [
                'name' => 'Klein Large',
                'driver' => 'pollination',
                'model' => 'klein-large',
                'description' => 'FLUX.2 Klein 9B - Higher quality image generation & editing on Modal',
                'supports_image_input' => true,
                'supports_video_output' => false,
                'is_default' => false,
                'sort_order' => 9,
            ],
            [
                'name' => 'Veo',
                'driver' => 'pollination',
                'model' => 'veo',
                'description' => 'Veo 3.1 Fast - Google\'s video generation model (preview)',
                'supports_image_input' => true,
                'supports_video_output' => true,
                'is_default' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Seedance',
                'driver' => 'pollination',
                'model' => 'seedance',
                'description' => 'Seedance Lite - BytePlus video generation (better quality)',
                'supports_image_input' => true,
                'supports_video_output' => true,
                'is_default' => false,
                'sort_order' => 11,
            ],
            [
                'name' => 'Seedance Pro',
                'driver' => 'pollination',
                'model' => 'seedance-pro',
                'description' => 'Seedance Pro-Fast - BytePlus video generation (better prompt adherence)',
                'supports_image_input' => true,
                'supports_video_output' => true,
                'is_default' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'Wan',
                'driver' => 'pollination',
                'model' => 'wan',
                'description' => 'Wan 2.6 - Alibaba text/image-to-video with audio (2-15s, up to 1080P) via DashScope',
                'supports_image_input' => true,
                'supports_video_output' => true,
                'is_default' => false,
                'sort_order' => 13,
            ],
            [
                'name' => 'Grok Video',
                'driver' => 'pollination',
                'model' => 'grok-video',
                'description' => 'Grok Video (api.airforce) - xAI video gen',
                'supports_image_input' => true,
                'supports_video_output' => true,
                'is_default' => false,
                'sort_order' => 14,
            ],
        ];

        foreach ($providers as $provider) {
            static::updateOrCreate(
                ['model' => $provider['model']],
                $provider
            );
        }
    }
}

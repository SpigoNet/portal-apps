<?php

namespace App\Modules\MundosDeMim\Database\Seeders;

use App\Modules\MundosDeMim\Models\AIProvider;
use Illuminate\Database\Seeder;

class AIProviderSeeder extends Seeder
{
    public function run(): void
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
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
                'is_active' => true,
                'sort_order' => 14,
            ],
        ];

        foreach ($providers as $provider) {
            AIProvider::updateOrCreate(
                ['model' => $provider['model']],
                $provider
            );
        }
    }
}

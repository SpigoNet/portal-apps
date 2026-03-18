<?php

namespace Tests\Unit\AI;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Modules\Admin\Services\AiProviderService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiProviderServiceTest extends TestCase
{
    #[Test]
    public function it_normalizes_pollinations_image_edit_driver_names_from_provider_metadata(): void
    {
        $provider = new AiProvider([
            'nome' => 'Pollinations Image Edit',
            'driver' => 'pollinations image edit',
            'url_json_modelos' => 'https://gen.pollinations.ai/v1/images/edits',
            'base_url' => 'https://gen.pollinations.ai',
        ]);

        $model = new AiModel([
            'nome' => 'Pollinations Image Edit',
            'modelo_id_externo' => 'nanobanana',
            'input_types' => ['image'],
            'output_types' => ['image'],
        ]);
        $model->setRelation('provedor', $provider);

        $service = new AiProviderService();

        $this->assertSame('pollination_image_edit', $service->getDriverForProvider($model));
    }
}

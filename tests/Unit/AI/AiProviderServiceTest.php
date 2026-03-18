<?php

namespace Tests\Unit\AI;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Modules\Admin\Services\AiProviderService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    #[Test]
    public function it_falls_back_to_a_supported_image_to_image_provider_when_default_driver_is_not_supported(): void
    {
        $unsupported = $this->makeImageModel('Gemini Vision', 'gemini');
        $supported = $this->makeImageModel('Pollinations Edit', 'pollination_image_edit');

        $service = new class($unsupported, [$unsupported, $supported]) extends AiProviderService
        {
            public function __construct(
                private ?AiModel $defaultModel,
                private array $models
            ) {}

            public function getModelForUserEntity(?\App\Models\User $user, string $inputType = 'image', string $outputType = 'image'): ?AiModel
            {
                return $this->defaultModel;
            }

            public function getActiveModels(): EloquentCollection
            {
                return new EloquentCollection($this->models);
            }
        };

        $this->assertSame($supported, $service->getImageToImageProvider());
    }

    private function makeImageModel(string $name, string $driver): AiModel
    {
        $provider = new AiProvider([
            'nome' => $name,
            'driver' => $driver,
        ]);

        $model = new AiModel([
            'nome' => $name,
            'modelo_id_externo' => str($name)->slug()->value(),
            'input_types' => ['image'],
            'output_types' => ['image'],
            'is_active' => true,
        ]);
        $model->setRelation('provedor', $provider);

        return $model;
    }
}

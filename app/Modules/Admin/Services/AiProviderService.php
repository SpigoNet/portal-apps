<?php

namespace App\Modules\Admin\Services;

use App\Models\AiModel;
use App\Models\AiModelDefault;
use App\Models\AiProvider;
use App\Models\AiUserModelDefault;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AiProviderService
{
    private const IMAGE_TO_IMAGE_DRIVERS = [
        'pollination',
        'pollination_image_edit',
        'airforce',
    ];

    private function getModelByDefaultMapping(string $inputType, string $outputType): ?AiModel
    {
        return AiModelDefault::getPadrao($inputType, $outputType);
    }

    private function getModelByUserMapping(?User $user, string $inputType, string $outputType): ?AiModel
    {
        if (! $user) {
            return null;
        }

        return AiUserModelDefault::getPadrao($user, $inputType, $outputType);
    }

    private function findFirstActiveModel(string $inputType, string $outputType): ?AiModel
    {
        return $this->getActiveModels()->first(function (AiModel $model) use ($inputType, $outputType) {
            return in_array($inputType, $model->input_types ?? [], true)
                && in_array($outputType, $model->output_types ?? [], true);
        });
    }

    public function getModelForUserEntity(?User $user, string $inputType = 'image', string $outputType = 'image'): ?AiModel
    {
        return $this->getModelByUserMapping($user, $inputType, $outputType)
            ?? $this->getModelByDefaultMapping($inputType, $outputType)
            ?? $this->findFirstActiveModel($inputType, $outputType);
    }

    public function getProviderForUser(?User $user, string $inputType = 'image', string $outputType = 'image'): ?AiModel
    {
        return $this->getModelForUserEntity($user, $inputType, $outputType);
    }

    public function getModelForUser(?User $user): string
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider ? $provider->model : 'nanobanana';
    }

    public function getDriverForUser(?User $user): string
    {
        $provider = $this->getModelForUserEntity($user);

        return $this->getDriverForProvider($provider);
    }

    public function getDriverForProvider(?AiModel $provider): string
    {
        if (! $provider) {
            return 'pollination';
        }

        return AiProvider::guessDriver(
            $provider->driver ?? $provider->provedor?->driver,
            $provider->nome ?? $provider->name ?? null,
            $provider->provedor?->url_json_modelos,
            $provider->provedor?->base_url
        ) ?? 'pollination';
    }

    public function getApiKeyForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $this->getApiKeyForProvider($provider);
    }

    public function getApiKeyForProvider(?AiModel $provider): ?string
    {
        return $provider?->provedor?->api_key;
    }

    public function getBaseUrlForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $this->getBaseUrlForProvider($provider);
    }

    public function getBaseUrlForProvider(?AiModel $provider): ?string
    {
        return $provider?->provedor?->base_url;
    }

    public function supportsImageInput(?User $user): bool
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider ? $provider->supports_image_input : true;
    }

    public function supportsVideoOutput(?User $user): bool
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider ? $provider->supports_video_output : false;
    }

    public function getActiveModels(): Collection
    {
        return AiModel::with('provedor')
            ->where('is_active', true)
            ->whereHas('provedor', fn ($query) => $query->where('is_active', true))
            ->orderBy('nome')
            ->get();
    }

    public function getActiveProviders(): Collection
    {
        return AiProvider::query()
            ->where('is_active', true)
            ->orderBy('nome')
            ->get();
    }

    public function getProviderCatalog(): Collection
    {
        return $this->getActiveProviders();
    }

    public function getVisionTextProvider(?User $user): ?AiModel
    {
        return $this->getModelForUserEntity($user, 'image', 'text');
    }

    public function supportsImageToImageProvider(?AiModel $provider): bool
    {
        if (! $provider) {
            return false;
        }

        return in_array($this->getDriverForProvider($provider), self::IMAGE_TO_IMAGE_DRIVERS, true)
            && in_array('image', $provider->input_types ?? [], true)
            && in_array('image', $provider->output_types ?? [], true);
    }

    public function getSupportedImageToImageModels(): Collection
    {
        return $this->getActiveModels()
            ->filter(fn (AiModel $model) => $this->supportsImageToImageProvider($model))
            ->values();
    }

    public function getImageToImageProvider(?User $user = null): ?AiModel
    {
        $provider = $this->getModelForUserEntity($user, 'image', 'image');

        if ($this->supportsImageToImageProvider($provider)) {
            return $provider;
        }

        return $this->getSupportedImageToImageModels()->first();
    }

    public function getTextToTextProvider(?User $user = null): ?AiModel
    {
        return $this->getModelForUserEntity($user, 'text', 'text');
    }
}

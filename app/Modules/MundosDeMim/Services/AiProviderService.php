<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\User;
use App\Modules\MundosDeMim\Models\AiGatewayProvider;
use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAiSetting;

class AiProviderService
{
    public function getModelForUserEntity(?User $user): ?AIProvider
    {
        if (! $user) {
            return AIProvider::getDefault();
        }

        $userSetting = UserAiSetting::where('user_id', $user->id)->first();

        if ($userSetting) {
            return $userSetting->aiProvider;
        }

        if ($user->mundos_de_mim_default_ai_provider_id) {
            return AIProvider::find($user->mundos_de_mim_default_ai_provider_id);
        }

        return AIProvider::getDefault();
    }

    public function getProviderForUser(?User $user): ?AIProvider
    {
        return $this->getModelForUserEntity($user);
    }

    public function getModelForUser(?User $user): string
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider ? $provider->model : 'nanobanana';
    }

    public function getDriverForUser(?User $user): string
    {
        $provider = $this->getModelForUserEntity($user);

        if (! $provider) {
            return 'pollination';
        }

        return $provider->gatewayProvider?->driver ?? $provider->driver ?? 'pollination';
    }

    public function getApiKeyForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider?->gatewayProvider?->api_key;
    }

    public function getBaseUrlForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $provider?->gatewayProvider?->base_url;
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

    public function getActiveModels(): \Illuminate\Database\Eloquent\Collection
    {
        return AIProvider::with('gatewayProvider')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getActiveProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getActiveModels();
    }

    public function getProviderCatalog(): \Illuminate\Database\Eloquent\Collection
    {
        return AiGatewayProvider::query()->orderBy('name')->get();
    }
}

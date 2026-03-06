<?php

namespace App\Modules\Admin\Services;

use App\Models\AiGatewayProvider;
use App\Models\User;
use App\Modules\Admin\Models\AIModeloPadrao;
use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAiSetting;

class AiProviderService
{
    private function getProviderByDefaultMapping(string $inputType, string $outputType): ?AIProvider
    {
        $modeloPadrao = AIModeloPadrao::getPadrao($inputType, $outputType);

        if ($modeloPadrao) {
            $provider = AIProvider::where('model', $modeloPadrao->nome)
                ->where('is_active', true)
                ->first();

            if ($provider) {
                return $provider;
            }
        }

        return null;
    }

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

        return $this->getDriverForProvider($provider);
    }

    public function getDriverForProvider(?AIProvider $provider): string
    {
        if (! $provider) {
            return 'pollination';
        }

        return $provider->gatewayProvider?->driver ?? $provider->driver ?? 'pollination';
    }

    public function getApiKeyForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $this->getApiKeyForProvider($provider);
    }

    public function getApiKeyForProvider(?AIProvider $provider): ?string
    {
        if (! $provider) {
            return null;
        }

        // 1. Tenta pelo link direto do Gateway Provider
        if ($provider->gatewayProvider) {
            $adminProvedor = \App\Modules\Admin\Models\AIProvedor::where('nome', $provider->gatewayProvider->name)->first();
            if ($adminProvedor && $adminProvedor->api_key) {
                return $adminProvedor->api_key;
            }

            if ($provider->gatewayProvider->api_key) {
                return $provider->gatewayProvider->api_key;
            }
        }

        // 2. Fallback: Se não tem gateway linkado, tenta pelo nome do Driver (ex: pollination -> Pollination)
        if ($provider->driver) {
            $adminProvedor = \App\Modules\Admin\Models\AIProvedor::where('nome', 'LIKE', $provider->driver)->first();
            if ($adminProvedor && $adminProvedor->api_key) {
                return $adminProvedor->api_key;
            }
        }

        return $provider->api_key; // Último recurso: chave no próprio modelo de provedor do MundosDeMim
    }

    public function getBaseUrlForUser(?User $user): ?string
    {
        $provider = $this->getModelForUserEntity($user);

        return $this->getBaseUrlForProvider($provider);
    }

    public function getBaseUrlForProvider(?AIProvider $provider): ?string
    {
        if (! $provider) {
            return null;
        }

        if ($provider->gatewayProvider) {
            $adminProvedor = \App\Modules\Admin\Models\AIProvedor::where('nome', $provider->gatewayProvider->name)->first();
            if ($adminProvedor && $adminProvedor->url_json_modelos) {
                // Fallback ou lógica futura para URL base do Admin
            }
        }

        return $provider->gatewayProvider?->base_url;
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

    public function getVisionTextProvider(?User $user): ?AIProvider
    {
        $provider = $this->getProviderByDefaultMapping('image', 'text');

        if ($provider) {
            return $provider;
        }

        return AIProvider::where('supports_image_input', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    public function getImageToImageProvider(?User $user = null): ?AIProvider
    {
        $provider = $this->getProviderByDefaultMapping('image', 'image');

        if ($provider) {
            return $provider;
        }

        return AIProvider::where('supports_image_input', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    public function getTextToTextProvider(): ?AIProvider
    {
        $provider = $this->getProviderByDefaultMapping('text', 'text');

        if ($provider) {
            return $provider;
        }

        return AIProvider::getDefault();
    }
}

<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\User;
use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAiSetting;

class AiProviderService
{
    public function getProviderForUser(?User $user): ?AIProvider
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

    public function getModelForUser(?User $user): string
    {
        $provider = $this->getProviderForUser($user);

        return $provider ? $provider->model : 'nanobanana';
    }

    public function getDriverForUser(?User $user): string
    {
        $provider = $this->getProviderForUser($user);

        return $provider ? $provider->driver : 'pollination';
    }

    public function supportsImageInput(?User $user): bool
    {
        $provider = $this->getProviderForUser($user);

        return $provider ? $provider->supports_image_input : true;
    }

    public function supportsVideoOutput(?User $user): bool
    {
        $provider = $this->getProviderForUser($user);

        return $provider ? $provider->supports_video_output : false;
    }

    public function getActiveProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return AIProvider::where('is_active', true)->orderBy('sort_order')->get();
    }
}

<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Modules\DspaceForms\Models\DspaceXmlConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait DspaceConfigSession
{
    protected function getConfigId(Request $request): ?int
    {
        $sessionKey = 'dspace_config_id_'.Auth::id();

        return $request->session()->get($sessionKey);
    }

    protected function setConfigId(Request $request, int $configId): void
    {
        $sessionKey = 'dspace_config_id_'.Auth::id();
        $request->session()->put($sessionKey, $configId);
    }

    protected function clearConfigId(Request $request): void
    {
        $sessionKey = 'dspace_config_id_'.Auth::id();
        $request->session()->forget($sessionKey);
    }

    protected function requireConfig(Request $request)
    {
        $configId = $this->getConfigId($request);

        if (! $configId) {
            return redirect()->route('dspace-forms.index')
                ->with('error', 'Selecione ou crie uma configuração primeiro.');
        }

        $config = DspaceXmlConfiguration::find($configId);

        if (! $config || $config->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado à configuração.');
        }

        return $config;
    }

    protected function getConfig(Request $request): ?DspaceXmlConfiguration
    {
        $configId = $this->getConfigId($request);

        if (! $configId) {
            return null;
        }

        $config = DspaceXmlConfiguration::find($configId);

        if (! $config || $config->user_id !== Auth::id()) {
            return null;
        }

        return $config;
    }
}

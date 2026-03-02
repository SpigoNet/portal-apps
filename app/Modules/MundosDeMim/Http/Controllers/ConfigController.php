<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function index()
    {
        $settings = DB::table('mundos_de_mim_ai_settings')->get();
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();

        return view('MundosDeMim::config.index', compact('settings', 'providers'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|exists:ai_providers,id',
        ]);

        foreach ($request->settings as $settingKey => $providerId) {
            DB::table('mundos_de_mim_ai_settings')
                ->where('setting_key', $settingKey)
                ->update(['ai_provider_id' => $providerId]);
        }

        return back()->with('success', 'Configurações salvas com sucesso.');
    }
}

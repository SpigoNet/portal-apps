<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAiSetting;
use App\Modules\MundosDeMim\Services\SyncPollinationModelsService;
use Illuminate\Http\Request;

class AdminAiProviderController extends Controller
{
    public function index()
    {
        $providers = AIProvider::orderBy('sort_order')->get();

        return view('MundosDeMim::admin.ai-providers.index', compact('providers'));
    }

    public function create()
    {
        return view('MundosDeMim::admin.ai-providers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:255',
            'model' => 'required|string|max:255|unique:mundos_de_mim_ai_providers,model',
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AIProvider::where('is_default', true)->update(['is_default' => false]);
        }

        AIProvider::create($validated);

        return redirect()->route('mundos-de-mim.admin.ai-providers.index')
            ->with('success', 'Provedor de IA criado com sucesso.');
    }

    public function edit($id)
    {
        $provider = AIProvider::findOrFail($id);

        return view('MundosDeMim::admin.ai-providers.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        $provider = AIProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:255',
            'model' => 'required|string|unique:mundos_de_mim_ai_providers,model,'.$id,
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AIProvider::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $provider->update($validated);

        return redirect()->route('mundos-de-mim.admin.ai-providers.index')
            ->with('success', 'Provedor de IA atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $provider = AIProvider::findOrFail($id);

        if ($provider->is_default) {
            return back()->with('error', 'Não é possível excluir o provedor padrão.');
        }

        $provider->delete();

        return back()->with('success', 'Provedor de IA excluído.');
    }

    public function setDefault($id)
    {
        AIProvider::where('is_default', true)->update(['is_default' => false]);
        $provider = AIProvider::findOrFail($id);
        $provider->update(['is_default' => true]);

        return back()->with('success', 'Provedor padrão alterado.');
    }

    public function seed()
    {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'App\\Modules\\MundosDeMim\\Database\\Seeders\\AIProviderSeeder']);

        return back()->with('success', 'Provedores padrão inseridos com sucesso.');
    }

    public function userSettings()
    {
        $users = User::whereHas('portalApps', function ($query) {
            $query->where('portal_app_id', 10);
        })->with(['mundosDeMimAiSetting', 'mundosDeMimDefaultAiProvider'])->get();

        $providers = AIProvider::where('is_active', true)->orderBy('name')->get();

        return view('MundosDeMim::admin.ai-providers.user-settings', compact('users', 'providers'));
    }

    public function updateUserSettings(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ai_provider_id' => 'required|exists:mundos_de_mim_ai_providers,id',
        ]);

        UserAiSetting::updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['ai_provider_id' => $validated['ai_provider_id']]
        );

        return back()->with('success', 'Configuração do usuário atualizada.');
    }

    public function updateGlobalDefault(Request $request)
    {
        $validated = $request->validate([
            'default_provider_id' => 'required|exists:mundos_de_mim_ai_providers,id',
        ]);

        AIProvider::where('is_default', true)->update(['is_default' => false]);
        AIProvider::findOrFail($validated['default_provider_id'])->update(['is_default' => true]);

        return back()->with('success', 'Provedor padrão global atualizado.');
    }

    public function syncPollination()
    {
        $service = new SyncPollinationModelsService;
        $result = $service->sync();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}

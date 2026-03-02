<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayProvider;
use App\Models\User;
use App\Modules\MundosDeMim\Models\AIProvider;
use App\Modules\MundosDeMim\Models\UserAiSetting;
use Illuminate\Http\Request;

class AdminAiModelController extends Controller
{
    public function index(Request $request)
    {
        $query = AIProvider::query()->with('gatewayProvider');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('type')) {
            if ($request->type === 'image') {
                $query->where('supports_image_input', true);
            } elseif ($request->type === 'video') {
                $query->where('supports_video_output', true);
            }
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $query->orderBy('sort_order')->orderBy('name');

        $models = $query->get();
        $providers = AiGatewayProvider::query()->orderBy('name')->get();

        return view('MundosDeMim::admin.ai-models.index', compact('models', 'providers'));
    }

    public function create()
    {
        $providers = AiGatewayProvider::query()->where('is_active', true)->orderBy('name')->get();

        return view('MundosDeMim::admin.ai-models.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:mundos_de_mim_providers,id',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255|unique:mundos_de_mim_ai_providers,model',
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $provider = AiGatewayProvider::findOrFail($validated['provider_id']);
        $validated['driver'] = $provider->driver;
        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AIProvider::where('is_default', true)->update(['is_default' => false]);
        }

        AIProvider::create($validated);

        return redirect()->route('mundos-de-mim.admin.ai-models.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit($id)
    {
        $model = AIProvider::findOrFail($id);
        $providers = AiGatewayProvider::query()->where('is_active', true)->orderBy('name')->get();

        return view('MundosDeMim::admin.ai-models.edit', compact('model', 'providers'));
    }

    public function update(Request $request, $id)
    {
        $model = AIProvider::findOrFail($id);

        $validated = $request->validate([
            'provider_id' => 'required|exists:mundos_de_mim_providers,id',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255|unique:mundos_de_mim_ai_providers,model,'.$id,
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $provider = AiGatewayProvider::findOrFail($validated['provider_id']);
        $validated['driver'] = $provider->driver;
        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AIProvider::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $model->update($validated);

        return redirect()->route('mundos-de-mim.admin.ai-models.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $model = AIProvider::findOrFail($id);

        if ($model->is_default) {
            return back()->with('error', 'Não é possível excluir o modelo padrão.');
        }

        $model->delete();

        return back()->with('success', 'Modelo excluído.');
    }

    public function setDefault($id)
    {
        AIProvider::where('is_default', true)->update(['is_default' => false]);
        AIProvider::findOrFail($id)->update(['is_default' => true]);

        return back()->with('success', 'Modelo padrão alterado.');
    }

    public function userSettings()
    {
        $users = User::whereHas('portalApps', function ($query) {
            $query->where('portal_app_id', 10);
        })->with(['mundosDeMimAiSetting', 'mundosDeMimDefaultAiProvider'])->get();

        $models = AIProvider::where('is_active', true)->with('gatewayProvider')->orderBy('name')->get();

        return view('MundosDeMim::admin.ai-models.user-settings', compact('users', 'models'));
    }

    public function updateUserSettings(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ai_provider_id' => 'nullable|exists:mundos_de_mim_ai_providers,id',
        ]);

        if (empty($validated['ai_provider_id'])) {
            UserAiSetting::where('user_id', $validated['user_id'])->delete();

            return back()->with('success', 'Configuração individual removida. Usuário voltará a usar padrão global.');
        }

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

        return back()->with('success', 'Modelo padrão global atualizado.');
    }
}

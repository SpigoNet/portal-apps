<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    public function index(Request $request)
    {
        $query = AiModel::query()->with('provider');

        $currentProvider = null;
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
            $currentProvider = AiProvider::find($request->provider_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $query->orderBy('sort_order')->orderBy('name');

        $models = $query->get();

        return view('admin.ai-models.index', compact('models', 'currentProvider'));
    }

    public function create(Request $request)
    {
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();
        $selectedProviderId = $request->query('provider_id');

        return view('admin.ai-models.create', compact('providers', 'selectedProviderId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255|unique:ai_models,model',
            'provider_id' => 'required|exists:ai_providers,id',
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $provider = AiProvider::findOrFail($validated['provider_id']);
        $validated['driver'] = $provider->driver;
        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AiModel::where('is_default', true)->where('provider_id', $validated['provider_id'])->update(['is_default' => false]);
        }

        AiModel::create($validated);

        return redirect()->route('admin.ai-models.index', ['provider_id' => $validated['provider_id']])
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit($id)
    {
        $model = AiModel::findOrFail($id);
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();

        return view('admin.ai-models.edit', compact('model', 'providers'));
    }

    public function update(Request $request, $id)
    {
        $model = AiModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255|unique:ai_models,model,'.$id,
            'provider_id' => 'required|exists:ai_providers,id',
            'description' => 'nullable|string',
            'supports_image_input' => 'boolean',
            'supports_video_output' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $provider = AiProvider::findOrFail($validated['provider_id']);
        $validated['driver'] = $provider->driver;
        $validated['supports_image_input'] = $request->has('supports_image_input');
        $validated['supports_video_output'] = $request->has('supports_video_output');
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_default']) {
            AiModel::where('is_default', true)->where('provider_id', $validated['provider_id'])->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $model->update($validated);

        return redirect()->route('admin.ai-models.index', ['provider_id' => $validated['provider_id']])
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $model = AiModel::findOrFail($id);
        $providerId = $model->provider_id;
        $model->delete();

        return redirect()->route('admin.ai-models.index', ['provider_id' => $providerId])
            ->with('success', 'Modelo excluído com sucesso.');
    }

    public function sync($providerId)
    {
        $provider = AiProvider::findOrFail($providerId);

        if (! $provider->sync_url) {
            return back()->with('error', 'Sync URL não configurada para este provedor.');
        }

        $serviceClass = match ($provider->driver) {
            'pollination' => \App\Modules\Admin\Services\SyncPollinationModelsService::class,
            'airforce' => \App\Modules\Admin\Services\SyncAirForceModelsService::class,
            default => null,
        };

        if (! $serviceClass) {
            return back()->with('error', 'Driver não suportado para sync: '.$provider->driver);
        }

        $service = new $serviceClass($provider);
        $result = $service->sync();

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}

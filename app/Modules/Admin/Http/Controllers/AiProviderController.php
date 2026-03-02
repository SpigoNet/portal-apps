<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\Request;

class AiProviderController extends Controller
{
    public function index()
    {
        $providers = AiProvider::query()->orderBy('name')->get();

        return view('admin.ai-providers.index', compact('providers'));
    }

    public function create()
    {
        return view('admin.ai-providers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:100|unique:ai_providers,driver',
            'input_type' => 'required|in:text,image',
            'output_type' => 'required|in:text,image,audio,video',
            'base_url' => 'nullable|url',
            'sync_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        AiProvider::create($validated);

        return redirect()->route('admin.ai-providers.index')
            ->with('success', 'Provedor criado com sucesso.');
    }

    public function edit($id)
    {
        $provider = AiProvider::findOrFail($id);

        return view('admin.ai-providers.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        $provider = AiProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:100|unique:ai_providers,driver,'.$provider->id,
            'input_type' => 'required|in:text,image',
            'output_type' => 'required|in:text,image,audio,video',
            'base_url' => 'nullable|url',
            'sync_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $provider->update($validated);

        return redirect()->route('admin.ai-providers.index')
            ->with('success', 'Provedor atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $provider = AiProvider::findOrFail($id);
        $provider->delete();

        return back()->with('success', 'Provedor excluído com sucesso.');
    }

    public function sync($id)
    {
        $provider = AiProvider::findOrFail($id);

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

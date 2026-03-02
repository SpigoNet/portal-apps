<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayProvider;
use App\Modules\MundosDeMim\Services\SyncAirForceModelsService;
use App\Modules\MundosDeMim\Services\SyncPollinationModelsService;
use Illuminate\Http\Request;

class AdminAiProviderController extends Controller
{
    public function index()
    {
        $providers = AiGatewayProvider::query()->orderBy('name')->get();

        return view('MundosDeMim::admin.providers.index', compact('providers'));
    }

    public function create()
    {
        return view('MundosDeMim::admin.providers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:100|unique:mundos_de_mim_providers,driver',
            'base_url' => 'nullable|url',
            'sync_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        AiGatewayProvider::create($validated);

        return redirect()->route('mundos-de-mim.admin.ai-providers.index')
            ->with('success', 'Provedor criado com sucesso.');
    }

    public function edit($id)
    {
        $provider = AiGatewayProvider::findOrFail($id);

        return view('MundosDeMim::admin.providers.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        $provider = AiGatewayProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|max:100|unique:mundos_de_mim_providers,driver,'.$provider->id,
            'base_url' => 'nullable|url',
            'sync_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $provider->update($validated);

        return redirect()->route('mundos-de-mim.admin.ai-providers.index')
            ->with('success', 'Provedor atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $provider = AiGatewayProvider::withCount('models')->findOrFail($id);

        if ($provider->models_count > 0) {
            return back()->with('error', 'Não é possível excluir o provedor com modelos vinculados.');
        }

        $provider->delete();

        return back()->with('success', 'Provedor excluído com sucesso.');
    }

    public function syncPollination()
    {
        $provider = AiGatewayProvider::where('driver', 'pollination')->first();

        if (! $provider) {
            return back()->with('error', 'Cadastre primeiro o provedor Pollination.');
        }

        $service = new SyncPollinationModelsService($provider);
        $result = $service->sync();

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function syncAirForce()
    {
        $provider = AiGatewayProvider::where('driver', 'airforce')->first();

        if (! $provider) {
            return back()->with('error', 'Cadastre primeiro o provedor AirForce.');
        }

        $service = new SyncAirForceModelsService($provider);
        $result = $service->sync();

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}

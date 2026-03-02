<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    public function index(Request $request)
    {
        $query = AiProvider::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('driver', 'like', "%{$search}%");
            });
        }

        if ($request->filled('input_type')) {
            $query->where('input_type', $request->input_type);
        }

        if ($request->filled('output_type')) {
            $query->where('output_type', $request->output_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $query->orderBy('name');

        $models = $query->get();
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();

        return view('admin.ai-models.index', compact('models', 'providers'));
    }

    public function create()
    {
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();

        return view('admin.ai-models.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'provider_id' => 'required|exists:ai_providers,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        AiProvider::create($validated);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit($id)
    {
        $model = AiProvider::findOrFail($id);
        $providers = AiProvider::where('is_active', true)->orderBy('name')->get();

        return view('admin.ai-models.edit', compact('model', 'providers'));
    }

    public function update(Request $request, $id)
    {
        $model = AiProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'provider_id' => 'required|exists:ai_providers,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|accepted',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $model->update($validated);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy($id)
    {
        $model = AiProvider::findOrFail($id);
        $model->delete();

        return back()->with('success', 'Modelo excluído com sucesso.');
    }
}

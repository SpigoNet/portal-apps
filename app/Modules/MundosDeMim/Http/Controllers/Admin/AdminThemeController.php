<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Theme;
use App\Modules\MundosDeMim\Models\ThemeExample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminThemeController extends Controller
{
    // ... index e create (sem mudanças) ...
    public function index()
    {
        $themes = Theme::withCount('prompts')->orderBy('created_at', 'desc')->get();
        return view('MundosDeMim::admin.themes.index', compact('themes'));
    }

    public function create()
    {
        return view('MundosDeMim::admin.themes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:mundos_de_mim_themes,slug|max:255',
            'age_rating' => 'required|in:kids,teen,adult',
            'is_seasonal' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            // Novos Campos de Upload
            'example_input_description' => 'nullable|string|max:100',
            'example_input' => 'nullable|image|max:2048', // Foto "Antes"
            'example_outputs.*' => 'nullable|image|max:2048', // Fotos "Depois" (Array)
        ]);

        $validated['is_seasonal'] = $request->has('is_seasonal');

        // 1. Upload da Imagem "Antes" (Input)
        if ($request->hasFile('example_input')) {
            $path = $request->file('example_input')->store('themes/inputs', 'public');
            $validated['example_input_path'] = $path;
        }

        $theme = Theme::create($validated);

        // 2. Upload das Imagens "Depois" (Outputs)
        if ($request->hasFile('example_outputs')) {
            foreach ($request->file('example_outputs') as $photo) {
                $path = $photo->store('themes/examples', 'public');
                $theme->examples()->create([
                    'image_path' => $path
                ]);
            }
        }

        return redirect()->route('mundos-de-mim.admin.themes.index')->with('success', 'Tema criado com imagens!');
    }

    public function edit($id)
    {
        // Carregamos também os exemplos para mostrar na galeria de edição
        $theme = Theme::with(['prompts', 'examples'])->findOrFail($id);
        return view('MundosDeMim::admin.themes.edit', compact('theme'));
    }

    public function update(Request $request, $id)
    {
        $theme = Theme::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:mundos_de_mim_themes,slug,'.$id,
            'age_rating' => 'required|in:kids,teen,adult',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'example_input_description' => 'nullable|string|max:100',
            'example_input' => 'nullable|image|max:2048',
            'example_outputs.*' => 'nullable|image|max:2048',
        ]);

        $validated['is_seasonal'] = $request->has('is_seasonal');

        // 1. Atualizar Imagem "Antes" (Se enviada nova, deleta antiga)
        if ($request->hasFile('example_input')) {
            if ($theme->example_input_path && Storage::disk('public')->exists($theme->example_input_path)) {
                Storage::disk('public')->delete($theme->example_input_path);
            }
            $validated['example_input_path'] = $request->file('example_input')->store('themes/inputs', 'public');
        }

        $theme->update($validated);

        // 2. Adicionar Novas Imagens "Depois" (Acumulativo)
        if ($request->hasFile('example_outputs')) {
            foreach ($request->file('example_outputs') as $photo) {
                $path = $photo->store('themes/examples', 'public');
                $theme->examples()->create([
                    'image_path' => $path
                ]);
            }
        }

        return redirect()->route('mundos-de-mim.admin.themes.index')->with('success', 'Tema atualizado.');
    }

    // Método para remover uma imagem específica da galeria "Depois"
    public function destroyExample($example_id)
    {
        $example = ThemeExample::findOrFail($example_id);

        // Deleta arquivo físico
        if (Storage::disk('public')->exists($example->image_path)) {
            Storage::disk('public')->delete($example->image_path);
        }

        $example->delete();

        return back()->with('success', 'Imagem de exemplo removida.');
    }

    public function destroy($id)
    {
        $theme = Theme::findOrFail($id);
        // O delete cascade do banco deve cuidar dos registros filhos,
        // mas idealmente deletaríamos os arquivos físicos aqui também.
        $theme->delete();
        return back()->with('success', 'Tema excluído.');
    }
}

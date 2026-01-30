<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Prompt;
use App\Modules\MundosDeMim\Models\Theme;
use Illuminate\Http\Request;

class AdminPromptController extends Controller
{
    public function create($theme_id)
    {
        $theme = Theme::findOrFail($theme_id);
        return view('MundosDeMim::admin.prompts.create', compact('theme'));
    }

    public function store(Request $request)
    {
        $this->savePrompt($request, new Prompt());
        return redirect()->route('mundos-de-mim.admin.themes.edit', $request->theme_id)
            ->with('success', 'Prompt criado com sucesso!');
    }

    public function edit($id)
    {
        // Carrega o prompt com seus requisitos para a view
        $prompt = Prompt::with('requirements')->findOrFail($id);
        return view('MundosDeMim::admin.prompts.edit', compact('prompt'));
    }

    public function update(Request $request, $id)
    {
        $prompt = Prompt::findOrFail($id);
        $this->savePrompt($request, $prompt);

        return redirect()->route('mundos-de-mim.admin.themes.edit', $prompt->theme_id)
            ->with('success', 'Prompt e requisitos atualizados!');
    }

    public function destroy($id)
    {
        Prompt::findOrFail($id)->delete();
        return back()->with('success', 'Prompt removido.');
    }

    /**
     * Lógica centralizada para Salvar/Atualizar
     */
    private function savePrompt(Request $request, Prompt $prompt)
    {
        $request->validate([
            'theme_id' => 'required|exists:mundos_de_mim_themes,id',
            'prompt_text' => 'required|string|min:5',
            'requirements' => 'nullable|array',
            'requirements.*.key' => 'required_with:requirements.*.value|string',
        ]);

        // 1. Salva dados básicos
        $prompt->theme_id = $request->theme_id;
        $prompt->prompt_text = $request->prompt_text;
        $prompt->save();

        // 2. Sincroniza Requisitos
        // Estratégia simples: Remove todos antigos e recria os novos enviados
        // Isso facilita muito o gerenciamento no front-end
        $prompt->requirements()->delete();

        if ($request->has('requirements')) {
            foreach ($request->requirements as $req) {
                if (!empty($req['key'])) {
                    $prompt->requirements()->create([
                        'requirement_key' => $req['key'],
                        'operator' => $req['operator'] ?? '=',
                        'requirement_value' => $req['value'],
                    ]);
                }
            }
        }
    }
}

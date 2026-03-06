<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\MundosDeMim\Models\Prompt;
use App\Modules\MundosDeMim\Models\Theme;
use App\Services\AI\Drivers\AirForceDriver;
use App\Services\AI\Drivers\KdjingpaiDriver;
use App\Services\AI\Drivers\PollinationDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function generateFromBefore($id)
    {
        $prompt = Prompt::with('theme')->findOrFail($id);
        $theme = $prompt->theme;

        if (! $theme || ! $theme->example_input_path) {
            return back()->with('error', 'Este tema não possui imagem "Antes" configurada.');
        }

        if (! Storage::disk('public')->exists($theme->example_input_path)) {
            return back()->with('error', 'A imagem "Antes" não foi encontrada no storage.');
        }

        $aiProviderService = new AiProviderService;
        $provider = $aiProviderService->getImageToImageProvider(auth()->user());

        if (! $provider) {
            return back()->with('error', 'Nenhum modelo de IA ativo para geração de imagem foi encontrado.');
        }

        $driverName = $aiProviderService->getDriverForProvider($provider);
        $apiKey = $aiProviderService->getApiKeyForProvider($provider);
        $baseUrl = $aiProviderService->getBaseUrlForProvider($provider);
        $driver = $this->createImageDriver($driverName, $provider->model, $apiKey, $baseUrl);

        $imageUrl = $driver->generateImage($prompt->prompt_text, [
            'reference_image_path' => $theme->example_input_path,
        ]);

        if (! $imageUrl) {
            return back()->with('error', 'Falha ao gerar imagem a partir do "Antes".');
        }

        $storedPath = $this->storeGeneratedImage($imageUrl, $theme->id);

        if (! $storedPath) {
            return back()->with('error', 'A imagem foi gerada, mas não foi possível salvar no storage.');
        }

        $theme->examples()->create([
            'image_path' => $storedPath,
        ]);

        return back()->with('success', 'Imagem gerada com sucesso a partir do "Antes" e adicionada aos resultados.');
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

    private function createImageDriver(string $driverName, ?string $model, ?string $apiKey, ?string $baseUrl)
    {
        return match ($driverName) {
            'airforce' => new AirForceDriver($model, $apiKey, $baseUrl),
            'kdjingpai' => new KdjingpaiDriver($model, $apiKey, $baseUrl),
            default => new PollinationDriver($model, $apiKey, $baseUrl),
        };
    }

    private function storeGeneratedImage(string $imageUrl, int $themeId): ?string
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (! $response->successful() || empty($response->body())) {
                return null;
            }

            $filename = 'themes/examples/generated-theme-'.$themeId.'-'.Str::uuid().'.jpg';
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

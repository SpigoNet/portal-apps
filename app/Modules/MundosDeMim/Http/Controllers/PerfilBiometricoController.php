<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Services\AI\Drivers\GeminiDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PerfilBiometricoController extends Controller
{
    public function index()
    {
        $attributes = UserAttribute::where('user_id', Auth::id())->first();

        return view('MundosDeMim::perfil.index', compact('attributes'));
    }

    public function analyze(Request $request)
    {
        try {
            $attributes = UserAttribute::where('user_id', Auth::id())->first();

            if (! $attributes || ! $attributes->photo_path || ! Storage::disk('public')->exists($attributes->photo_path)) {
                return response()->json(['error' => 'Foto de referência não encontrada. Por favor, faça upload de uma foto primeiro.'], 400);
            }

            $config = AntConfiguracao::first();
            $apiKey = $config->ia_key ?? env('GEMINI_API_KEY');

            if (! $apiKey) {
                return response()->json(['error' => 'Chave API do Gemini não configurada.'], 500);
            }

            $driver = new GeminiDriver($apiKey);

            $messages = [
                ['role' => 'system', 'content' => 'Você transforma uma foto de referencia em uma descricao visual util para geracao de imagens. Analise somente o que e visivel, sem inventar gostos, personalidade ou historia. Retorne APENAS um JSON valido com as chaves: "visual_profile", "style_and_wardrobe", "body_type", "eye_color", "hair_type". Em "visual_profile", descreva em portugues e em texto corrido tom de pele, olhos, cabelo, formato/comprimento/textura do cabelo, tracos unicos visiveis e qualquer detalhe marcante util para preservar a identidade. Em "style_and_wardrobe", descreva roupas, acessorios e estetica visivel, se houver informacao suficiente. Seja especifico, respeitoso e conciso.'],
                [
                    'role' => 'user',
                    'content' => 'Analise meu perfil visual com base nesta foto e sugira textos para o meu perfil.',
                    'image' => $attributes->photo_path,
                ],
            ];

            $result = $driver->generateText($messages, ['jsonMode' => true]);

            // Tenta limpar possíveis marcações de markdown do JSON
            $cleanResult = preg_replace('/```json|```/', '', $result);
            $json = json_decode(trim($cleanResult), true);

            if (! $json) {
                Log::error('MundosDeMim: Falha ao decodificar JSON da IA: '.$result);

                return response()->json(['error' => 'A IA não retornou um formato válido. Tente novamente.'], 500);
            }

            return response()->json($json);

        } catch (\Exception $e) {
            Log::error('MundosDeMim Error: '.$e->getMessage());

            return response()->json(['error' => 'Erro ao processar análise: '.$e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'visual_profile' => 'required|string|min:20|max:5000',
            'personality_vibe' => 'nullable|string|max:3000',
            'interests_and_symbols' => 'nullable|string|max:3000',
            'style_and_wardrobe' => 'nullable|string|max:3000',
            'favorite_settings' => 'nullable|string|max:3000',
            'identity_details' => 'nullable|string|max:3000',
            'avoid_in_generations' => 'nullable|string|max:3000',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:20|max:300',
            'body_type' => 'nullable|string|max:50',
            'eye_color' => 'nullable|string|max:50',
            'hair_type' => 'nullable|string|max:50',
            'notification_preference' => 'nullable|in:none,whatsapp,telegram,email',
        ]);

        $attributes = UserAttribute::firstOrNew(['user_id' => Auth::id()]);

        if ($request->hasFile('photo')) {
            if ($attributes->photo_path && Storage::disk('public')->exists($attributes->photo_path)) {
                Storage::disk('public')->delete($attributes->photo_path);
            }

            $path = $request->file('photo')->store('user_references', 'public');
            $attributes->photo_path = $path;
        }

        $attributes->visual_profile = trim($validated['visual_profile']);
        $attributes->personality_vibe = $this->nullableTrim($validated['personality_vibe'] ?? null);
        $attributes->interests_and_symbols = $this->nullableTrim($validated['interests_and_symbols'] ?? null);
        $attributes->style_and_wardrobe = $this->nullableTrim($validated['style_and_wardrobe'] ?? null);
        $attributes->favorite_settings = $this->nullableTrim($validated['favorite_settings'] ?? null);
        $attributes->identity_details = $this->nullableTrim($validated['identity_details'] ?? null);
        $attributes->avoid_in_generations = $this->nullableTrim($validated['avoid_in_generations'] ?? null);
        $attributes->height = $validated['height'] ?? $attributes->height;
        $attributes->weight = $validated['weight'] ?? $attributes->weight;
        $attributes->body_type = $this->nullableTrim($validated['body_type'] ?? null) ?? $attributes->body_type ?? 'não informado';
        $attributes->eye_color = $this->nullableTrim($validated['eye_color'] ?? null) ?? $attributes->eye_color ?? 'não informado';
        $attributes->hair_type = $this->nullableTrim($validated['hair_type'] ?? null) ?? $attributes->hair_type ?? 'não informado';
        $attributes->notification_preference = $validated['notification_preference'] ?? 'none';

        $attributes->save();

        return redirect()->route('mundos-de-mim.perfil.index')
            ->with('success', 'Perfil e foto de referência atualizados com sucesso.');
    }

    protected function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Services\AI\Drivers\GeminiDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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

            if (!$attributes || !$attributes->photo_path || !Storage::disk('public')->exists($attributes->photo_path)) {
                return response()->json(['error' => 'Foto de referência não encontrada. Por favor, faça upload de uma foto primeiro.'], 400);
            }

            $config = AntConfiguracao::first();
            $apiKey = $config->ia_key ?? env('GEMINI_API_KEY');

            if (!$apiKey) {
                return response()->json(['error' => 'Chave API do Gemini não configurada.'], 500);
            }

            $driver = new GeminiDriver($apiKey);

            $messages = [
                ['role' => 'system', 'content' => 'Você é um especialista em análise física para criação de avatares. Analise a imagem fornecida e retorne um JSON com as seguintes chaves: "body_type", "eye_color", "hair_type". Seja breve e use termos em português adequados para prompts (ex: "atlético", "olhos castanhos", "cabelo curto preto"). Responda APENAS o JSON.'],
                [
                    'role' => 'user',
                    'content' => 'Analise meu perfil físico baseado nesta foto.',
                    'image' => $attributes->photo_path
                ]
            ];

            $result = $driver->generateText($messages, ['jsonMode' => true]);

            // Tenta limpar possíveis marcações de markdown do JSON
            $cleanResult = preg_replace('/```json|```/', '', $result);
            $json = json_decode(trim($cleanResult), true);

            if (!$json) {
                Log::error('MundosDeMim: Falha ao decodificar JSON da IA: ' . $result);
                return response()->json(['error' => 'A IA não retornou um formato válido. Tente novamente.'], 500);
            }

            return response()->json($json);

        } catch (\Exception $e) {
            Log::error('MundosDeMim Error: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar análise: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        // 1. Validação
        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:20|max:300',
            'body_type' => 'required|string|max:50',
            'eye_color' => 'required|string|max:50',
            'hair_type' => 'required|string|max:50',
        ]);

        // 2. Busca ou inicia o objeto
        $attributes = UserAttribute::firstOrNew(['user_id' => Auth::id()]);

        // 3. Lógica de Upload da Foto
        if ($request->hasFile('photo')) {
            // Se já existir uma foto anterior, deletar para não acumular lixo
            if ($attributes->photo_path && Storage::exists($attributes->photo_path)) {
                Storage::delete($attributes->photo_path);
            }

            // Salva na pasta 'user_references' (pode ser disco 'public' ou 's3')
            $path = $request->file('photo')->store('user_references', 'public');
            $attributes->photo_path = $path;
        }

        // 4. Preenche os demais dados
        $attributes->height = $validated['height'];
        $attributes->weight = $validated['weight'];
        $attributes->body_type = $validated['body_type'];
        $attributes->eye_color = $validated['eye_color'];
        $attributes->hair_type = $validated['hair_type'];

        $attributes->save();

        return redirect()->route('mundos-de-mim.perfil.index')
            ->with('success', 'Perfil biométrico e foto atualizados com sucesso.');
    }
}

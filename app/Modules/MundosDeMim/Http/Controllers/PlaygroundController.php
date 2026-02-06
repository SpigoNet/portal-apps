<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Modules\ANT\Models\AntConfiguracao; // Para pegar a chave do Gemini
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\PollinationDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlaygroundController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $attributes = UserAttribute::where('user_id', $user->id)->first();

        $hasPhoto = $attributes && !empty($attributes->photo_path) && Storage::disk('public')->exists($attributes->photo_path);

        return view('MundosDeMim::playground.index', compact('attributes', 'hasPhoto'));
    }

    public function refine(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3',
        ]);

        try {
            $user = Auth::user();
            $attributes = UserAttribute::where('user_id', $user->id)->first();

            $config = AntConfiguracao::first();
            $apiKey = $config->ia_key ?? env('GEMINI_API_KEY');

            if (!$apiKey) {
                return response()->json(['error' => 'Chave API do Gemini não configurada.'], 500);
            }

            $driver = new GeminiDriver($apiKey);

            // Monta contexto biográfico
            $bio = "";
            if ($attributes) {
                $bio = "O usuário tem as seguintes características: ";
                if ($attributes->eye_color)
                    $bio .= "olhos {$attributes->eye_color}, ";
                if ($attributes->hair_type)
                    $bio .= "cabelo {$attributes->hair_type}, ";
                if ($attributes->body_type)
                    $bio .= "tipo físico {$attributes->body_type}. ";
            }

            $systemPrompt = "Você é um mestre de Prompt Engineering para modelos de imagem como Midjourney e Stable Diffusion. 
            Sua tarefa é pegar uma ideia simples do usuário e transformá-la em um prompt visual rico, cinematográfico e detalhado, em INGLÊS.
            
            REGRAS:
            1. Use a descrição física do usuário se fornecida para manter a consistência.
            2. Adicione detalhes de iluminação, estilo artístico (ex: 8k, unreal engine 5, cinematic lighting).
            3. Mantenha o foco na ideia original do usuário.
            4. Retorne APENAS o prompt refinado em inglês, sem explicações.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Refine este prompt: '{$request->input('prompt')}'. {$bio}"]
            ];

            $refinedPrompt = $driver->generateText($messages);

            return response()->json(['refined' => trim($refinedPrompt)]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao refinar prompt: ' . $e->getMessage()], 500);
        }
    }

    public function generate(Request $request)
    {
        // Validação básica
        $request->validate([
            'prompt' => 'required|string|min:3|max:2000',
            'driver' => 'required|in:gemini,pollination', // Valida a escolha
        ]);

        $user = Auth::user();
        $attributes = UserAttribute::where('user_id', $user->id)->first();

        // Configurações e Instanciação do Driver Escolhido
        $driverName = $request->input('driver');
        $driver = null;

        // Opções comuns (caminho da foto, se houver)
        $photoPath = ($attributes && !empty($attributes->photo_path)) ? $attributes->photo_path : null;

        try {
            if ($driverName === 'gemini') {
                // --- LÓGICA GEMINI (Texto/Análise) ---

                // Busca chave no banco ou .env
                $config = AntConfiguracao::first();
                $apiKey = $config->ia_key ?? env('GEMINI_API_KEY');

                if (!$apiKey) {
                    return back()->with('error', 'Chave API do Gemini não configurada (Tabela ant_configuracao).');
                }

                $driver = new GeminiDriver($apiKey);

                // Monta mensagem multimodal
                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
                    [
                        'role' => 'user',
                        'content' => $request->input('prompt'),
                        'image' => $photoPath // Driver do Gemini sabe lidar com isso
                    ]
                ];

                $result = $driver->generateText($messages);

                // Formata retorno como Texto
                $htmlResult = "<div class='prose bg-gray-50 p-4 rounded border'>" . nl2br(e($result)) . "</div>";

            } else {
                // --- LÓGICA POLLINATIONS (Imagem) ---

                // Verifica créditos se não for admin
                if ($user->credits <= 0 && !auth()->user()->can('admin-do-app')) {
                    return back()->with('error', 'Você não possui créditos suficientes para gerar imagens sob demanda. Os créditos são renovados semanalmente.');
                }

                $driver = new PollinationDriver();

                $options = [];
                if ($photoPath) {
                    $options['reference_image_path'] = $photoPath;
                }

                $imageUrl = $driver->generateImage($request->input('prompt'), $options);

                if ($imageUrl) {
                    // Deduz crédito
                    $user->decrement('credits');

                    $htmlResult = "
                            <div class='text-center space-y-4'>
                                <div class='bg-green-50 text-green-700 text-xs py-1 px-3 rounded-full inline-block mb-2'>-1 Crédito Adquirido</div>
                                <h3 class='font-bold text-lg text-indigo-700'>Imagem Gerada:</h3>
                                <a href='{$imageUrl}' target='_blank'>
                                    <img src='{$imageUrl}' class='rounded shadow-xl mx-auto max-w-full border-4 border-white' style='max-height: 500px;'>
                                </a>
                                <div class='flex justify-center gap-2 mt-4'>
                                    <a href='{$imageUrl}' download class='bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2'>
                                        <i class='fa-solid fa-download'></i> Baixar
                                    </a>
                                </div>
                            </div>
                        ";
                } else {
                    return back()->with('error', 'O Pollinations não retornou uma imagem válida.');
                }
            }

            return back()->with('result', $htmlResult)->withInput();

        } catch (\Exception $e) {
            return back()->with('error', 'Erro no Playground: ' . $e->getMessage());
        }
    }
}

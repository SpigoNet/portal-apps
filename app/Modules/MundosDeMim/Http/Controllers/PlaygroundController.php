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
                $htmlResult = "<div class='prose bg-gray-50 p-4 rounded border'>".nl2br(e($result))."</div>";

            } else {
                // --- LÓGICA POLLINATIONS (Imagem) ---

                $driver = new PollinationDriver(); // Chave é opcional/hardcoded na classe

                $options = [];
                if ($photoPath) {
                    $options['reference_image_path'] = $photoPath;
                }

                // Chama método específico de imagem
                $imageUrl = $driver->generateImage($request->input('prompt'), $options);

                if ($imageUrl) {
                    // Formata retorno como Imagem
                    $htmlResult = "
                        <div class='text-center space-y-4'>
                            <h3 class='font-bold text-lg text-indigo-700'>Imagem Gerada pelo Pollinations:</h3>
                            <a href='{$imageUrl}' target='_blank'>
                                <img src='{$imageUrl}' class='rounded shadow-lg mx-auto max-w-full border-4 border-white' style='max-height: 500px;'>
                            </a>
                            <p class='text-xs text-gray-500'>Caminho: {$imageUrl}</p>
                            <a href='{$imageUrl}' download class='inline-block bg-indigo-600 text-white px-4 py-2 rounded text-sm'>Baixar Imagem</a>
                        </div>
                    ";
                } else {
                    return back()->with('error', 'O Pollinations não retornou uma imagem válida. Verifique os logs.');
                }
            }

            return back()->with('result', $htmlResult)->withInput();

        } catch (\Exception $e) {
            return back()->with('error', 'Erro no Playground: ' . $e->getMessage());
        }
    }
}

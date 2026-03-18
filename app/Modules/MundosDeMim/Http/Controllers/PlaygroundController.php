<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\User;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use App\Modules\MundosDeMim\Models\Prompt;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Services\AI\Drivers\AirForceDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\KdjingpaiDriver;
use App\Services\AI\Drivers\LmStudioDriver;
use App\Services\AI\Drivers\PollinationDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PlaygroundController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();
        $selectedUserId = session('playground_user_id', $currentUser->id);
        $targetUser = User::findOrFail($selectedUserId);

        $attributes = UserAttribute::where('user_id', $targetUser->id)->first();
        $aiProviderService = new AiProviderService;
        $providers = $aiProviderService->getActiveModels()
            ->filter(fn (AiModel $model) => $model->supports_image_input)
            ->values();
        $selectedProvider = $aiProviderService->getImageToImageProvider($targetUser);

        $hasPhoto = $attributes && ! empty($attributes->photo_path) && Storage::disk('public')->exists($attributes->photo_path);

        if ($currentUser->can('admin-do-app')) {
            $allUsers = User::select('users.*')
                ->join('mundos_de_mim_user_attributes', 'users.id', '=', 'mundos_de_mim_user_attributes.user_id')
                ->whereNotNull('mundos_de_mim_user_attributes.photo_path')
                ->where('mundos_de_mim_user_attributes.photo_path', '!=', '')
                ->orderBy('users.name')
                ->get();
        } else {
            $allUsers = collect();
        }

        $prompts = Prompt::with(['theme.examples'])->orderBy('theme_id')->get();
        $latestPrompt = Prompt::latest()->first();
        $recentGenerations = DailyGeneration::where('user_id', $targetUser->id)
            ->latest()
            ->limit(8)
            ->get();

        return view('MundosDeMim::playground.index', compact('attributes', 'hasPhoto', 'providers', 'selectedProvider', 'targetUser', 'allUsers', 'currentUser', 'prompts', 'latestPrompt', 'recentGenerations'));
    }

    public function refine(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3',
        ]);

        try {
            $user = $this->getTargetUser();
            $attributes = UserAttribute::where('user_id', $user->id)->first();
            $aiService = new AiProviderService;
            $provider = $aiService->getTextToTextProvider();

            if (! $provider) {
                return response()->json(['error' => 'Nenhum modelo padrão text->text ativo configurado no portal.'], 422);
            }

            $driverName = $aiService->getDriverForProvider($provider);
            $apiKey = $aiService->getApiKeyForProvider($provider);
            $baseUrl = $aiService->getBaseUrlForProvider($provider);

            $driver = $this->createTextDriver($driverName, $provider->model, $apiKey, $baseUrl);

            $profileContext = '';
            if ($attributes) {
                $contextLines = [];

                foreach ($attributes->promptContextSections() as $label => $content) {
                    $contextLines[] = "- {$label}: {$content}";
                }

                if ($avoid = $attributes->avoidPromptContext()) {
                    $contextLines[] = "- evitar: {$avoid}";
                }

                if ($contextLines !== []) {
                    $profileContext = "<perfil_usuario>\n".implode("\n", $contextLines)."\n</perfil_usuario>";
                }
            }

            $systemPrompt = 'You are an expert prompt engineer for image-to-image generation.
            Rewrite the user prompt into a high-quality ENGLISH prompt suitable for tools like Midjourney/Stable Diffusion, preserving the original idea.

            Use the provided user profile as high-priority context for identity, vibe, clothing, preferences, favorite settings, and distinctive details. Never invent profile details that are not present in the provided context.

            Adicione essas regras de identidade e qualidade ao refinar adicionando ao prompt:            
            1. The generated image MUST be a variation of the original reference person, not a new person.
            2. Keep the same face identity, facial structure, and expression from the reference image.
            3. Preserve all unique human traits exactly as they are, including birthmarks, moles, freckles, skin spots, scars, asymmetries, and any distinctive facial detail.
            4. Do not beautify, replace, remove, or invent facial traits.
            5. If any style request conflicts with identity preservation, identity preservation always wins.
            6. It is very important to clearly state at the beginning that the original person\'s photo should be used as a reference, maintaining the appearance, face, hair type, eyes, and expression of the person in the original photo.

            QUALITY RULES:
            1. Add concise cinematic visual details (lighting, lens, composition, texture) without changing identity.
            2. Keep the result clear and production-ready.
            3. Return ONLY the final refined prompt text in English, with no explanation.';

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Refine este prompt: '{$request->input('prompt')}'.\n\n{$profileContext}"],
            ];

            $refinedPrompt = $driver->generateText($messages, ['model' => $provider->model]);

            if (! $refinedPrompt) {
                return response()->json(['error' => 'Não foi possível refinar o prompt no momento.'], 500);
            }

            return response()->json(['refined' => trim($refinedPrompt)]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao refinar prompt: '.$e->getMessage()], 500);
        }
    }

    public function generate(Request $request)
    {
        // Validação básica
        $request->validate([
            'prompt' => 'required|string|min:3',
            'prompt_id' => 'nullable|exists:mundos_de_mim_prompts,id',
            'ai_provider_id' => 'nullable|exists:ai_modelos,id',
            'send_to_user' => 'nullable|boolean',
        ]);

        $currentUser = Auth::user();
        $user = $this->getTargetUser();
        $attributes = UserAttribute::where('user_id', $user->id)->first();
        $provider = $this->resolveModel($user, $request->input('ai_provider_id'));
        $aiService = new AiProviderService;
        $driverName = $aiService->getDriverForProvider($provider);
        $apiKey = $aiService->getApiKeyForProvider($provider);
        $baseUrl = $aiService->getBaseUrlForProvider($provider);

        if (! $provider) {
            return back()->with('error', 'Nenhum provedor de IA ativo encontrado.')->withInput();
        }

        // Opções comuns (caminho da foto, se houver)
        $photoPath = ($attributes && ! empty($attributes->photo_path) && Storage::disk('public')->exists($attributes->photo_path))
            ? $attributes->photo_path
            : null;

        try {
            if ($driverName !== 'pollination' && $driverName !== 'airforce') {
                return back()->with('error', "O driver '{$driverName}' ainda não é suportado no Playground.")->withInput();
            }

            if (! $photoPath) {
                return back()->with('error', 'Envie uma foto de referência no seu perfil antes de gerar uma nova imagem.')->withInput();
            }

            // Verifica créditos se não for admin
            if ($user->credits <= 0 && ! $currentUser->can('admin-do-app')) {
                return back()->with('error', 'Você não possui créditos suficientes para gerar imagens sob demanda. Os créditos são renovados semanalmente.')->withInput();
            }

            $driver = $this->createDriver($driverName, $provider->model, $apiKey, $baseUrl);

            $options = [];
            if ($photoPath) {
                $options['reference_image_path'] = $photoPath;
            }

            // Se um prompt_id for selecionado, usar o texto do prompt
            $promptText = $request->input('prompt');
            if ($request->filled('prompt_id')) {
                $savedPrompt = Prompt::find($request->prompt_id);
                if ($savedPrompt) {
                    $promptText = $savedPrompt->prompt_text;
                }
            }

            $finalPrompt = $this->buildGenerationPrompt($attributes, $promptText);

            \Illuminate\Support\Facades\Log::debug('Playground generate', [
                'driver' => $driverName,
                'model' => $provider->model,
                'hasApiKey' => ! empty($apiKey),
                'baseUrl' => $baseUrl,
                'hasPhoto' => ! empty($photoPath),
                'options' => $options,
                'originalPrompt' => $promptText,
                'finalPrompt' => $finalPrompt,
            ]);

            $imageUrl = $driver->generateImage($finalPrompt, $options);

            $sendToUser = $request->boolean('send_to_user');

            if ($imageUrl) {
                // Deduz crédito
                $user->decrement('credits');

                // Salva na Galeria
                $savedPromptId = $request->input('prompt_id');
                $themeId = null;
                if ($savedPromptId) {
                    $savedPrompt = Prompt::find($savedPromptId);
                    $themeId = $savedPrompt?->theme_id;
                }

                DailyGeneration::create([
                    'user_id' => $user->id,
                    'theme_id' => $themeId,
                    'prompt_id' => $savedPromptId,
                    'image_url' => $imageUrl,
                    'final_prompt_used' => $finalPrompt,
                    'reference_date' => now()->toDateString(),
                ]);

                $providerLabel = e(($provider->gatewayProvider?->name ?? 'Sem provedor').' / '.$provider->name.' ('.$provider->model.')');
                $htmlResult = "
                        <div class='text-center space-y-4'>
                            <div class='bg-green-50 text-green-700 text-xs py-1 px-3 rounded-full inline-block mb-2'>-1 Crédito Adquirido</div>
                            <h3 class='font-bold text-lg text-indigo-700'>Imagem Gerada:</h3>
                            <div class='text-xs text-gray-500'>Provedor: {$providerLabel}</div>
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

                // Enviar para o usuário se solicitado
                if ($sendToUser) {
                    $this->sendToUser($user, $imageUrl);
                    $htmlResult = str_replace('</div>', "<div class='mt-4 bg-green-100 text-green-700 text-xs py-2 px-3 rounded'>✉️ Imagem enviada para o usuário!</div></div>", $htmlResult);
                }
            } else {
                return back()->with('error', 'O provedor selecionado não retornou uma imagem válida.')->withInput();
            }

            return back()->with('result', $htmlResult)->withInput();

        } catch (\Exception $e) {
            return back()->with('error', 'Erro no Playground: '.$e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3',
        ]);

        $user = $this->getTargetUser();
        $attributes = UserAttribute::where('user_id', $user->id)->first();
        $hasPhoto = $attributes
            && ! empty($attributes->photo_path)
            && Storage::disk('public')->exists($attributes->photo_path);

        return response()->json([
            'preview' => $this->buildGenerationPrompt($attributes, $request->input('prompt')),
            'has_photo' => $hasPhoto,
        ]);
    }

    protected function buildGenerationPrompt(?UserAttribute $attributes, string $promptText): string
    {
        if ($attributes) {
            return $attributes->buildImageGenerationPrompt($promptText);
        }

        $promptText = trim($promptText);

        return 'Use a imagem de referencia anexada para preservar a mesma pessoa. Pedido principal: '.$promptText.'. Resultado final com identidade preservada e alta qualidade.';
    }

    private function resolveModel($user, ?string $providerId = null): ?AiModel
    {
        $service = new AiProviderService;

        if ($providerId) {
            return $service->getActiveModels()->firstWhere('id', (int) $providerId);
        }

        return $service->getImageToImageProvider($user);
    }

    private function createDriver(string $driverName, ?string $model, ?string $apiKey, ?string $baseUrl)
    {
        return match ($driverName) {
            'airforce' => new AirForceDriver($model, $apiKey, $baseUrl),
            'kdjingpai' => new KdjingpaiDriver($model, $apiKey, $baseUrl),
            default => new PollinationDriver($model, $apiKey, $baseUrl),
        };
    }

    private function createTextDriver(string $driverName, ?string $model, ?string $apiKey, ?string $baseUrl)
    {
        return match ($driverName) {
            'airforce' => new AirForceDriver($model, $apiKey, $baseUrl),
            'kdjingpai' => new KdjingpaiDriver($model, $apiKey, $baseUrl),
            'gemini' => new GeminiDriver($apiKey),
            'lm_studio' => new LmStudioDriver($baseUrl),
            default => new PollinationDriver($model, $apiKey, $baseUrl),
        };
    }

    public function selectUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if (! auth()->user()->can('admin-do-app')) {
            abort(403);
        }

        session(['playground_user_id' => $request->user_id]);

        return redirect()->route('mundos-de-mim.playground.index');
    }

    private function getTargetUser(): \App\Models\User
    {
        $currentUser = Auth::user();
        $selectedUserId = session('playground_user_id', $currentUser->id);

        if ($currentUser->can('admin-do-app') && $selectedUserId !== $currentUser->id) {
            return User::findOrFail($selectedUserId);
        }

        return $currentUser;
    }

    private function sendToUser(User $user, string $imageUrl): bool
    {
        try {
            Mail::send([], [], function ($message) use ($user, $imageUrl) {
                $message->to($user->email, $user->name)
                    ->subject('🌟 Sua Nova Imagem Gerada!')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; color: #1e1e24;'>
                            <h1 style='color: #6366f1;'>Olá, {$user->name}!</h1>
                            <p style='color: #333333;'>Uma nova imagem foi gerada para você!</p>
                            <p><a href='{$imageUrl}' target='_blank'><img src='{$imageUrl}' style='max-width: 100%; border-radius: 10px;'/></a></p>
                            <p><a href='{$imageUrl}' target='_blank' style='color: #6366f1;'>Clique aqui para ver a imagem em tamanho maior</a></p>
                            <br>
                            <p style='color: #555555; font-size: 14px;'>Atenciosamente,<br>Equipe Mundos de Mim</p>
                        </div>
                    ");
            });

            \Illuminate\Support\Facades\Log::info("Playground: Imagem enviada para {$user->email}");

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Playground: Erro ao enviar email: '.$e->getMessage());

            return false;
        }
    }
}

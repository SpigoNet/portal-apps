<?php

namespace App\Modules\MundosDeMim\Services;

use App\Models\DailyNotification;
use App\Modules\Admin\Services\AiProviderService;
use App\Modules\MundosDeMim\Models\DailyGeneration;
use App\Modules\MundosDeMim\Models\RelatedPerson;
use App\Modules\MundosDeMim\Models\UserAttribute;
use App\Services\AI\Drivers\AirForceDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\KdjingpaiDriver;
use App\Services\AI\Drivers\LmStudioDriver;
use App\Services\AI\Drivers\OllamaDriver;
use App\Services\AI\Drivers\OpenAiCompatibleDriver;
use App\Services\AI\Drivers\PollinationDriver;
use App\Services\AI\Drivers\PollinationImageEditDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailyPhotoService
{
    protected ?string $driver = null;

    protected ?string $model = null;

    protected ?string $apiKey = null;

    protected ?string $baseUrl = null;

    public function __construct()
    {
        $service = new AiProviderService;
        $provider = $service->getImageToImageProvider();

        if ($provider && $provider->provedor) {
            $this->driver = $service->getDriverForProvider($provider);
            $this->model = $provider->model;
            $this->apiKey = $service->getApiKeyForProvider($provider);
            $this->baseUrl = $service->getBaseUrlForProvider($provider);
        }
    }

    public function processAll(): array
    {
        $users = UserAttribute::whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->where('notification_preference', '!=', 'none')
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($users as $userAttr) {
            try {
                $result = $this->processUser($userAttr);
                if ($result) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("DailyPhotoService: Erro ao processar usuário {$userAttr->user_id}: ".$e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $users->count(),
        ];
    }

    public function processUser(UserAttribute $userAttr): bool
    {
        $preference = $userAttr->notification_preference;

        if (! $preference || $preference === 'none') {
            return false;
        }

        $prompt = $this->buildPrompt($userAttr);
        $imageUrl = $this->generateImage($userAttr, $prompt);

        if (! $imageUrl) {
            Log::error("DailyPhotoService: Falha ao gerar imagem para usuário {$userAttr->user_id}");

            return false;
        }

        $generation = $this->saveGeneration($userAttr, $imageUrl, $prompt);

        return $this->sendNotification($userAttr, $imageUrl, $prompt, $preference, $generation?->id);
    }

    protected function generateImage(UserAttribute $userAttr, string $prompt): ?string
    {
        if (! $this->driver) {
            Log::error('DailyPhotoService: Nenhum provedor de IA ativo configurado');

            return null;
        }

        $options = [];
        if ($userAttr->photo_path && in_array($this->driver, ['pollination', 'pollination_image_edit', 'airforce', 'gemini', 'openai'], true)) {
            $options['reference_image_path'] = $userAttr->photo_path;
        }

        $driver = $this->createDriver();

        return $driver->generateImage($prompt, $options);
    }

    protected function buildPrompt(UserAttribute $userAttr): string
    {
        return $userAttr->buildImageGenerationPrompt(
            'Crie a imagem do dia desta pessoa usando o perfil completo para definir visual, energia, estilo, cenarios e preferencias.'
        );
    }

    protected function createDriver()
    {
        return match ($this->driver) {
            'airforce' => new AirForceDriver($this->model, $this->apiKey, $this->baseUrl),
            'openai' => new OpenAiCompatibleDriver($this->model, $this->apiKey, $this->baseUrl),
            'gemini' => new GeminiDriver($this->apiKey, $this->model, $this->baseUrl),
            'kdjingpai' => new KdjingpaiDriver($this->model, $this->apiKey, $this->baseUrl),
            'pollination_image_edit' => new PollinationImageEditDriver($this->model, $this->apiKey, $this->baseUrl),
            default => new PollinationDriver($this->model, $this->apiKey, $this->baseUrl),
        };
    }

    public function sendNotification(UserAttribute $userAttr, string $imageUrl, string $prompt, string $preference, ?int $generationId = null): bool
    {
        $user = $userAttr->user;

        if (! $user) {
            return false;
        }

        $generation = $generationId ? DailyGeneration::with('theme')->find($generationId) : null;
        $mundo = $generation?->theme?->name;
        $story = $this->resolveStory($user, $generation, $prompt);

        $notification = DailyNotification::create([
            'user_id' => $user->id,
            'generation_id' => $generationId,
            'image_url' => $imageUrl,
            'channel' => $preference,
            'sent' => false,
        ]);

        return match ($preference) {
            'email' => $this->sendEmail($user, $imageUrl, $story, $mundo, $notification),
            'telegram' => $this->sendTelegram($user, $imageUrl, $story, $mundo, $notification),
            'whatsapp' => $this->sendWhatsapp($user, $imageUrl, $story, $mundo, $notification),
            default => false,
        };
    }

    /**
     * Devolve a história do postal: a já guardada na geração, ou uma nova (que passa a ser guardada).
     */
    public function resolveStory($user, ?DailyGeneration $generation, string $prompt): string
    {
        if ($generation && filled($generation->story)) {
            return $generation->story;
        }

        $mundo = $generation?->theme?->name;
        $story = $this->generateStory($user, $mundo, $prompt) ?? $this->fallbackStory($user, $mundo);

        if ($generation) {
            try {
                $generation->update(['story' => $story]);
            } catch (\Throwable $e) {
                Log::warning('DailyPhotoService: não foi possível guardar a história: '.$e->getMessage());
            }
        }

        return $story;
    }

    protected function generateStory($user, ?string $mundo, string $prompt): ?string
    {
        try {
            $aiProviderService = new AiProviderService;
            $provider = $aiProviderService->getTextToTextProvider();

            if (! $provider) {
                return null;
            }

            $driver = $this->createTextDriver(
                $aiProviderService->getDriverForProvider($provider),
                $provider->model,
                $aiProviderService->getApiKeyForProvider($provider),
                $aiProviderService->getBaseUrlForProvider($provider)
            );

            $response = $driver->generateText($this->buildStoryPrompt($user, $mundo, $prompt), []);

            return $this->cleanStory($response);
        } catch (\Throwable $e) {
            Log::error('DailyPhotoService: Erro ao gerar a história do postal: '.$e->getMessage());

            return null;
        }
    }

    protected function buildStoryPrompt($user, ?string $mundo, string $imagePrompt): array
    {
        $primeiroNome = \Illuminate\Support\Str::before(trim((string) $user->name), ' ') ?: 'você';
        $imagePrompt = \Illuminate\Support\Str::limit(trim($imagePrompt), 1200, '');

        $companhias = RelatedPerson::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('name')
            ->filter(fn ($nome) => filled($nome) && mb_stripos($imagePrompt, $nome) !== false)
            ->values()
            ->all();

        $linhas = [
            'Você escreve o texto de um postal do app "Mundos de Mim": cada dia o usuário recebe a imagem de um mundo paralelo onde ele vive.',
            'Escreva em português do Brasil uma história curta, de 1 a 2 parágrafos (no máximo 700 caracteres no total), como se o postal tivesse sido enviado de lá.',
            "A pessoa se chama {$primeiroNome}. Conte a cena na segunda pessoa (\"você\") ou como um recado de quem está lá, com um toque de curiosidade e carinho.",
            $mundo ? "O nome do mundo é \"{$mundo}\"; cite-o uma vez." : 'Não cite nome de mundo.',
            $companhias ? 'Quem aparece junto: '.implode(', ', $companhias).'. Cite essas pessoas pelo nome.' : 'Não invente outras pessoas.',
            'Use apenas o que a descrição da imagem permite. Não invente fatos, lugares ou objetos que não estejam nela.',
            'Não use markdown, listas, emojis, títulos nem saudação com o nome. Responda somente com o texto da história.',
            '',
            "Descrição da imagem (em inglês ou português, é um prompt técnico): \"{$imagePrompt}\"",
        ];

        return [['role' => 'user', 'content' => implode("\n", $linhas)]];
    }

    protected function createTextDriver(string $driverName, ?string $model, ?string $apiKey, ?string $baseUrl)
    {
        return match ($driverName) {
            'airforce' => new AirForceDriver($model, $apiKey, $baseUrl),
            'openai' => new OpenAiCompatibleDriver($model, $apiKey, $baseUrl),
            'kdjingpai' => new KdjingpaiDriver($model, $apiKey, $baseUrl),
            'gemini' => new GeminiDriver($apiKey, $model, $baseUrl),
            'lm_studio' => new LmStudioDriver($baseUrl),
            'ollama' => new OllamaDriver($model, $apiKey, $baseUrl),
            default => new PollinationDriver($model, $apiKey, $baseUrl),
        };
    }

    protected function cleanStory(mixed $response): ?string
    {
        if (! is_string($response)) {
            return null;
        }

        $texto = trim(preg_replace('/[*_#`>]+/u', '', $response));
        $texto = trim(preg_replace("/\n{3,}/", "\n\n", $texto));

        if (mb_strlen($texto) < 40) {
            return null;
        }

        return \Illuminate\Support\Str::limit($texto, 900, '…');
    }

    protected function fallbackStory($user, ?string $mundo): string
    {
        $primeiroNome = \Illuminate\Support\Str::before(trim((string) $user->name), ' ');
        $saudacao = $primeiroNome !== '' ? "{$primeiroNome}, " : '';

        return $mundo
            ? "{$saudacao}este postal chegou direto de {$mundo}. Hoje o mundo foi um pouco mais seu: confira cada detalhe e imagine o que vem amanhã."
            : "{$saudacao}este postal chegou de um dos seus mundos. Confira cada detalhe e imagine o que vem amanhã.";
    }

    protected function saveGeneration(UserAttribute $userAttr, string $imageUrl, string $prompt): ?DailyGeneration
    {
        try {
            return DailyGeneration::create([
                'user_id' => $userAttr->user_id,
                'image_url' => $imageUrl,
                'final_prompt_used' => $prompt,
                'reference_date' => now()->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DailyPhotoService: Erro ao salvar geração: '.$e->getMessage());

            return null;
        }
    }

    protected function sendEmail($user, string $imageUrl, string $story, ?string $mundo, DailyNotification $notification): bool
    {
        try {
            $emailBody = $this->renderEmail($user, $imageUrl, $story, $mundo, $notification->generation_id);
            $assunto = $mundo ? "Seu postal de hoje chegou: {$mundo}" : 'Seu postal de hoje chegou';

            Mail::send([], [], function ($message) use ($user, $emailBody, $assunto) {
                $message->to($user->email, $user->name)
                    ->subject($assunto)
                    ->html($emailBody);
            });

            Log::info("DailyPhotoService: Email enviado para {$user->email}");

            $notification->markAsSent($emailBody);

            return true;
        } catch (\Exception $e) {
            Log::error("DailyPhotoService: Erro ao enviar email para {$user->email}: ".$e->getMessage());

            $notification->markAsFailed($e->getMessage());

            return false;
        }
    }

    protected function renderEmail($user, string $imageUrl, string $story, ?string $mundo, ?int $generationId): string
    {
        $notas = [];

        if ($generationId) {
            foreach (range(1, 5) as $nota) {
                $notas[$nota] = \Illuminate\Support\Facades\URL::signedRoute('mundos-de-mim.rate', ['generation' => $generationId, 'rating' => $nota]);
            }
        }

        return view('MundosDeMim::emails.postal', [
            'primeiroNome' => \Illuminate\Support\Str::before(trim((string) $user->name), ' '),
            'imageUrl' => $imageUrl,
            'story' => $story,
            'mundo' => $mundo,
            'notas' => $notas,
            'atlasUrl' => route('mundos-de-mim.galeria.index'),
        ])->render();
    }

    protected function sendTelegram($user, string $imageUrl, string $story, ?string $mundo, DailyNotification $notification): bool
    {
        $telegramId = $user->telegram_id ?? null;

        if (! $telegramId) {
            Log::warning("DailyPhotoService: Usuário {$user->id} não tem Telegram ID configurado");

            $notification->markAsFailed('Telegram ID não configurado');

            return false;
        }

        $token = (string) config('services.mundos_de_mim.telegram_token', '');

        if ($token === '') {
            Log::warning('DailyPhotoService: MUNDOS_DE_MIM_TELEGRAM_TOKEN não configurado');

            $notification->markAsFailed('Token do bot do Telegram não configurado');

            return false;
        }

        try {
            $legenda = ($mundo ? '<b>'.e($mundo)."</b>\n\n" : '').e($story);
            $base = 'https://api.telegram.org/bot'.$token;
            $timeout = (int) config('services.telegram.timeout', 15);

            $resposta = \Illuminate\Support\Facades\Http::timeout($timeout)->post($base.'/sendPhoto', [
                'chat_id' => $telegramId,
                'photo' => $imageUrl,
                'caption' => mb_substr($legenda, 0, 1024),
                'parse_mode' => 'HTML',
            ]);

            if (! $resposta->successful()) {
                throw new \RuntimeException('Telegram respondeu '.$resposta->status());
            }

            Log::info("DailyPhotoService: Telegram enviado para {$telegramId}");

            $notification->markAsSent($legenda);

            return true;
        } catch (\Exception $e) {
            Log::error('DailyPhotoService: Erro ao enviar Telegram: '.$e->getMessage());

            $notification->markAsFailed($e->getMessage());

            return false;
        }
    }

    protected function sendWhatsapp($user, string $imageUrl, string $story, ?string $mundo, DailyNotification $notification): bool
    {
        // Implementação futura quando a API do WhatsApp estiver disponível
        Log::info("DailyPhotoService: WhatsApp ainda não implementado para usuário {$user->id}");

        $notification->markAsFailed('WhatsApp não implementado');

        return false;
    }
}

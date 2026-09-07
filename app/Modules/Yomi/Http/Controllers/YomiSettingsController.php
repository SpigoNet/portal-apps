<?php

namespace App\Modules\Yomi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Providers\ProviderResolver;
use App\Modules\Yomi\Services\YomiSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class YomiSettingsController extends Controller
{
    public function __construct(
        protected YomiSettingsService $settings,
        protected ProviderResolver $resolver,
    ) {}

    public function edit(): View
    {
        $form = $this->settings->form();

        return view('Yomi::settings', [
            'providers' => $form['providers'],
            'priority' => $form['priority'],
            'labels' => collect(ProviderName::cases())
                ->mapWithKeys(fn (ProviderName $provider): array => [$provider->value => $provider->label()])
                ->all(),
            'descriptions' => collect(ProviderName::cases())
                ->mapWithKeys(fn (ProviderName $provider): array => [$provider->value => $provider->description()])
                ->all(),
            'credentialFields' => collect(ProviderName::cases())
                ->mapWithKeys(fn (ProviderName $provider): array => [$provider->value => $provider->credentialFields()])
                ->all(),
            'healthUrl' => route('yomi.settings.providers.health', ['provider' => '__PROVIDER__']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'priority' => ['sometimes', 'array', 'max:'.count(ProviderName::cases())],
            'priority.*' => ['required', Rule::enum(ProviderName::class)],
            'providers' => ['required', 'array'],
            'providers.*.enabled' => ['nullable', 'boolean'],
            'providers.*.base_url' => ['nullable', 'url:http,https'],
            'providers.*.credentials' => ['nullable', 'array'],
            'providers.*.credentials.*' => ['nullable', 'string', 'max:512'],
        ]);

        if (count(array_unique($data['priority'] ?? [])) !== count($data['priority'] ?? [])) {
            throw ValidationException::withMessages([
                'priority' => 'A prioridade não pode repetir provedores.',
            ]);
        }

        $enabled = $this->enabledProviders($data['providers']);

        if ($enabled->isEmpty()) {
            throw ValidationException::withMessages([
                'providers' => 'Pelo menos um provedor deve permanecer ativo.',
            ]);
        }

        $data['priority'] = array_values(array_unique([
            ...array_map('strval', $data['priority'] ?? []),
            ...$enabled->keys()->all(),
        ]));

        $this->settings->save($data);

        return Redirect::route('yomi.settings')
            ->with('status', 'Configurações salvas com sucesso.');
    }

    public function health(string $provider): JsonResponse
    {
        $name = ProviderName::tryFrom($provider);

        if ($name === null) {
            return response()->json([
                'provider' => $provider,
                'status' => 'invalid',
                'latency_ms' => null,
                'message' => 'Provedor inválido.',
            ], 422);
        }

        try {
            $result = $this->resolver->resolve($name)->health();
        } catch (Throwable $exception) {
            return response()->json([
                'provider' => $name->value,
                'status' => 'error',
                'latency_ms' => null,
                'message' => 'Falha ao verificar o estado de saúde: '.$exception->getMessage(),
            ]);
        }

        return response()->json($result);
    }

    /**
     * @param  array<string, mixed>  $providers
     */
    private function enabledProviders(array $providers): Collection
    {
        return collect($providers)->filter(fn (array $config): bool => ! empty($config['enabled']));
    }
}

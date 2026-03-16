<?php

use App\Models\AiProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureProviderColumns();
        $this->ensureUserDefaultsTable();
        $this->migrateEnglishProviders();
        $this->migrateMundosProviders();
        $this->migrateEnglishModels();
        $this->migrateMundosModels();
        $this->migrateEnglishDefaults();
        $this->migrateMundosDefaults();
        $this->migrateUserDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_modelos_padrao_usuario');
    }

    private function ensureProviderColumns(): void
    {
        Schema::table('ai_provedores', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_provedores', 'driver')) {
                $table->string('driver')->nullable()->after('nome');
            }

            if (! Schema::hasColumn('ai_provedores', 'base_url')) {
                $table->text('base_url')->nullable()->after('url_json_modelos');
            }

            if (! Schema::hasColumn('ai_provedores', 'api_key')) {
                $table->text('api_key')->nullable()->after('base_url');
            }

            if (! Schema::hasColumn('ai_provedores', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('api_key');
            }
        });

        DB::table('ai_provedores')->orderBy('id')->get()->each(function (object $provider): void {
            $driver = AiProvider::guessDriver(
                $provider->driver ?? null,
                $provider->nome ?? null,
                $provider->url_json_modelos ?? null,
                $provider->base_url ?? null
            );

            DB::table('ai_provedores')
                ->where('id', $provider->id)
                ->update([
                    'driver' => $driver,
                    'is_active' => $provider->is_active ?? true,
                ]);
        });
    }

    private function ensureUserDefaultsTable(): void
    {
        if (Schema::hasTable('ai_modelos_padrao_usuario')) {
            return;
        }

        Schema::create('ai_modelos_padrao_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('input_type');
            $table->string('output_type');
            $table->foreignId('ai_modelo_id')->constrained('ai_modelos')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'input_type', 'output_type'], 'ai_modelos_padrao_usuario_unique');
        });
    }

    private function migrateEnglishProviders(): void
    {
        if (! Schema::hasTable('ai_providers')) {
            return;
        }

        foreach (DB::table('ai_providers')->orderBy('id')->get() as $provider) {
            $nome = $provider->name ?: ucfirst((string) $provider->driver);
            $existing = DB::table('ai_provedores')
                ->where('driver', $provider->driver)
                ->orWhere('nome', $nome)
                ->first();

            $payload = [
                'nome' => $existing->nome ?? $nome,
                'driver' => $provider->driver ?: ($existing->driver ?? null),
                'url_json_modelos' => $existing->url_json_modelos ?? ($provider->sync_url ?? null),
                'base_url' => $existing->base_url ?? ($provider->base_url ?? null),
                'api_key' => $existing->api_key ?? ($provider->api_key ?? null),
                'default_input_types' => $existing->default_input_types
                    ?? json_encode(array_values(array_filter([$provider->input_type ?? null]))),
                'default_output_types' => $existing->default_output_types
                    ?? json_encode(array_values(array_filter([$provider->output_type ?? null]))),
                'is_active' => $existing->is_active ?? ($provider->is_active ?? true),
                'created_at' => $existing->created_at ?? ($provider->created_at ?? now()),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('ai_provedores')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('ai_provedores')->insert($payload);
            }
        }
    }

    private function migrateMundosProviders(): void
    {
        if (! Schema::hasTable('mundos_de_mim_providers')) {
            return;
        }

        foreach (DB::table('mundos_de_mim_providers')->orderBy('id')->get() as $provider) {
            $existing = DB::table('ai_provedores')
                ->where('driver', $provider->driver)
                ->orWhere('nome', $provider->name)
                ->first();

            $payload = [
                'nome' => $existing->nome ?? $provider->name,
                'driver' => $provider->driver,
                'url_json_modelos' => $existing->url_json_modelos ?? ($provider->sync_url ?? null),
                'base_url' => $existing->base_url ?? ($provider->base_url ?? null),
                'api_key' => $existing->api_key ?? ($provider->api_key ?? null),
                'default_input_types' => $existing->default_input_types ?? json_encode(['text']),
                'default_output_types' => $existing->default_output_types ?? json_encode(['image']),
                'is_active' => $existing->is_active ?? ($provider->is_active ?? true),
                'created_at' => $existing->created_at ?? ($provider->created_at ?? now()),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('ai_provedores')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('ai_provedores')->insert($payload);
            }
        }
    }

    private function migrateEnglishModels(): void
    {
        if (! Schema::hasTable('ai_models') || ! Schema::hasTable('ai_providers')) {
            return;
        }

        $providers = DB::table('ai_providers')->get()->keyBy('id');

        foreach (DB::table('ai_models')->orderBy('id')->get() as $model) {
            $legacyProvider = $providers->get($model->provider_id);
            $centralProviderId = $this->findCentralProviderId(
                $legacyProvider->driver ?? null,
                $legacyProvider->name ?? null
            );

            if (! $centralProviderId) {
                continue;
            }

            $inputTypes = array_values(array_unique(array_filter([
                $legacyProvider->input_type ?? 'text',
                $model->supports_image_input ? 'image' : null,
            ])));

            $outputTypes = [$model->supports_video_output ? 'video' : ($legacyProvider->output_type ?? 'image')];

            $this->upsertCentralModel(
                $centralProviderId,
                $model->model,
                [
                    'nome' => $model->name,
                    'descricao' => $model->description,
                    'input_types' => $inputTypes,
                    'output_types' => $outputTypes,
                    'pricing' => $this->decodeJsonValue($model->pricing),
                    'raw_data' => [
                        'migrated_from' => 'ai_models',
                        'legacy_id' => $model->id,
                        'driver' => $model->driver,
                        'supports_image_input' => (bool) $model->supports_image_input,
                        'supports_video_output' => (bool) $model->supports_video_output,
                        'paid_only' => (bool) $model->paid_only,
                    ],
                    'is_active' => (bool) $model->is_active,
                ]
            );
        }
    }

    private function migrateMundosModels(): void
    {
        if (! Schema::hasTable('mundos_de_mim_ai_providers')) {
            return;
        }

        $legacyProviders = Schema::hasTable('mundos_de_mim_providers')
            ? DB::table('mundos_de_mim_providers')->get()->keyBy('id')
            : collect();

        foreach (DB::table('mundos_de_mim_ai_providers')->orderBy('id')->get() as $model) {
            $legacyProvider = $legacyProviders->get($model->provider_id);
            $centralProviderId = $this->findCentralProviderId(
                $legacyProvider->driver ?? $model->driver ?? null,
                $legacyProvider->name ?? null
            );

            if (! $centralProviderId) {
                continue;
            }

            $inputTypes = ['text'];
            if ($model->supports_image_input) {
                $inputTypes[] = 'image';
            }

            $outputTypes = [$model->supports_video_output ? 'video' : 'image'];

            $this->upsertCentralModel(
                $centralProviderId,
                $model->model,
                [
                    'nome' => $model->name,
                    'descricao' => $model->description,
                    'input_types' => array_values(array_unique($inputTypes)),
                    'output_types' => $outputTypes,
                    'pricing' => $this->decodeJsonValue($model->pricing),
                    'raw_data' => [
                        'migrated_from' => 'mundos_de_mim_ai_providers',
                        'legacy_id' => $model->id,
                        'driver' => $model->driver,
                        'supports_image_input' => (bool) $model->supports_image_input,
                        'supports_video_output' => (bool) $model->supports_video_output,
                        'is_default' => (bool) $model->is_default,
                        'paid_only' => (bool) $model->paid_only,
                    ],
                    'is_active' => (bool) $model->is_active,
                ]
            );
        }
    }

    private function migrateEnglishDefaults(): void
    {
        if (! Schema::hasTable('ai_models') || ! Schema::hasTable('ai_providers')) {
            return;
        }

        $providers = DB::table('ai_providers')->get()->keyBy('id');

        foreach (DB::table('ai_models')->where('is_default', true)->get() as $model) {
            $legacyProvider = $providers->get($model->provider_id);
            $centralModelId = $this->findCentralModelId(
                $legacyProvider->driver ?? null,
                $model->model
            );

            if (! $centralModelId) {
                continue;
            }

            DB::table('ai_modelos_padrao')->updateOrInsert(
                [
                    'input_type' => $legacyProvider->input_type ?? 'text',
                    'output_type' => $legacyProvider->output_type ?? 'image',
                ],
                [
                    'ai_modelo_id' => $centralModelId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function migrateMundosDefaults(): void
    {
        if (! Schema::hasTable('mundos_de_mim_ai_settings')) {
            return;
        }

        $mapping = [
            'image_to_image' => ['image', 'image'],
            'text_to_image' => ['text', 'image'],
            'image_to_video' => ['image', 'video'],
        ];

        foreach (DB::table('mundos_de_mim_ai_settings')->whereNotNull('ai_provider_id')->get() as $setting) {
            [$inputType, $outputType] = $mapping[$setting->setting_key] ?? ['image', 'image'];
            $legacyModel = Schema::hasTable('mundos_de_mim_ai_providers')
                ? DB::table('mundos_de_mim_ai_providers')->where('id', $setting->ai_provider_id)->first()
                : null;

            if (! $legacyModel) {
                continue;
            }

            $centralModelId = $this->findCentralModelId($legacyModel->driver, $legacyModel->model);
            if (! $centralModelId) {
                continue;
            }

            DB::table('ai_modelos_padrao')->updateOrInsert(
                ['input_type' => $inputType, 'output_type' => $outputType],
                ['ai_modelo_id' => $centralModelId, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function migrateUserDefaults(): void
    {
        if (Schema::hasTable('mundos_de_mim_user_ai_settings')) {
            foreach (DB::table('mundos_de_mim_user_ai_settings')->get() as $setting) {
                $legacyModel = Schema::hasTable('mundos_de_mim_ai_providers')
                    ? DB::table('mundos_de_mim_ai_providers')->where('id', $setting->ai_provider_id)->first()
                    : null;

                if (! $legacyModel) {
                    continue;
                }

                $centralModelId = $this->findCentralModelId($legacyModel->driver, $legacyModel->model);
                if (! $centralModelId) {
                    continue;
                }

                DB::table('ai_modelos_padrao_usuario')->updateOrInsert(
                    ['user_id' => $setting->user_id, 'input_type' => 'image', 'output_type' => 'image'],
                    ['ai_modelo_id' => $centralModelId, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        if (! Schema::hasColumn('users', 'mundos_de_mim_default_ai_provider_id') || ! Schema::hasTable('mundos_de_mim_ai_providers')) {
            return;
        }

        foreach (DB::table('users')->whereNotNull('mundos_de_mim_default_ai_provider_id')->get() as $user) {
            $legacyModel = DB::table('mundos_de_mim_ai_providers')
                ->where('id', $user->mundos_de_mim_default_ai_provider_id)
                ->first();

            if (! $legacyModel) {
                continue;
            }

            $centralModelId = $this->findCentralModelId($legacyModel->driver, $legacyModel->model);
            if (! $centralModelId) {
                continue;
            }

            DB::table('ai_modelos_padrao_usuario')->updateOrInsert(
                ['user_id' => $user->id, 'input_type' => 'image', 'output_type' => 'image'],
                ['ai_modelo_id' => $centralModelId, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function upsertCentralModel(int $providerId, string $externalId, array $attributes): void
    {
        $existing = DB::table('ai_modelos')
            ->where('ai_provedor_id', $providerId)
            ->where('modelo_id_externo', $externalId)
            ->first();

        $payload = [
            'ai_provedor_id' => $providerId,
            'modelo_id_externo' => $externalId,
            'nome' => $attributes['nome'],
            'descricao' => $attributes['descricao'],
            'input_types' => json_encode($attributes['input_types'], JSON_UNESCAPED_UNICODE),
            'output_types' => json_encode($attributes['output_types'], JSON_UNESCAPED_UNICODE),
            'pricing' => $attributes['pricing'] ? json_encode($attributes['pricing'], JSON_UNESCAPED_UNICODE) : null,
            'raw_data' => json_encode($attributes['raw_data'], JSON_UNESCAPED_UNICODE),
            'is_active' => $attributes['is_active'],
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_modelos')->where('id', $existing->id)->update($payload);

            return;
        }

        $payload['created_at'] = now();
        DB::table('ai_modelos')->insert($payload);
    }

    private function findCentralProviderId(?string $driver, ?string $name = null): ?int
    {
        $driver = AiProvider::guessDriver($driver, $name);

        $provider = DB::table('ai_provedores')
            ->when($driver, fn ($query) => $query->where('driver', $driver))
            ->when($name, fn ($query) => $query->orWhere('nome', $name))
            ->orderBy('id')
            ->first();

        return $provider?->id;
    }

    private function findCentralModelId(?string $driver, string $externalId): ?int
    {
        $driver = AiProvider::guessDriver($driver);

        $model = DB::table('ai_modelos')
            ->join('ai_provedores', 'ai_provedores.id', '=', 'ai_modelos.ai_provedor_id')
            ->where('ai_modelos.modelo_id_externo', $externalId)
            ->when($driver, fn ($query) => $query->where('ai_provedores.driver', $driver))
            ->select('ai_modelos.id')
            ->orderBy('ai_modelos.id')
            ->first();

        return $model?->id;
    }

    private function decodeJsonValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
};

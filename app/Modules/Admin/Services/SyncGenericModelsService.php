<?php

namespace App\Modules\Admin\Services;

use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGenericModelsService
{
    public function __construct(private ?AiProvider $provider = null)
    {
    }

    public function sync(): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        try {
            $apiUrl = $this->provider?->sync_url;

            if (! $apiUrl) {
                return [
                    'success' => false,
                    'message' => 'Sync URL não configurada para este provedor.',
                ];
            }

            $response = Http::timeout(30)->get($apiUrl);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch models: '.$response->status(),
                ];
            }

            $data = $response->json();
            $models = $data['data'] ?? [];

            foreach ($models as $modelData) {
                try {
                    $this->syncModel($modelData, $results);
                } catch (\Exception $e) {
                    $results['errors'][] = "Error syncing model {$modelData['id']}: {$e->getMessage()}";
                    Log::error("Error syncing model {$modelData['id']}", ['error' => $e->getMessage()]);
                }
            }

            return [
                'success' => true,
                'message' => "Sincronizados: {$results['created']} criados, {$results['updated']} atualizados.",
                'details' => $results,
            ];

        } catch (\Exception $e) {
            Log::error('SyncGenericModelsService Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erro ao buscar modelos: '.$e->getMessage(),
            ];
        }
    }

    protected function syncModel(array $modelData, array &$results): void
    {
        $modelId = $modelData['id'];
        $driver = $this->provider?->driver ?: 'generic';

        $existingModel = AiModel::where('model', $modelId)
            ->where('provider_id', $this->provider?->id)
            ->first();

        $data = [
            'provider_id' => $this->provider?->id,
            'name' => $this->formatName($modelId),
            'driver' => $driver,
            'model' => $modelId,
            'supports_image_input' => false,
            'supports_video_output' => false,
            'is_active' => true,
            'sort_order' => $existingModel?->sort_order ?? $this->getNextSortOrder(),
        ];

        if ($existingModel) {
            $existingModel->update($data);
            $results['updated']++;
        } else {
            AiModel::create($data);
            $results['created']++;
        }
    }

    protected function formatName(string $modelId): string
    {
        $name = str_replace(['-', '_'], ' ', $modelId);

        return ucwords($name);
    }

    protected function getNextSortOrder(): int
    {
        return AiModel::max('sort_order') + 1;
    }
}

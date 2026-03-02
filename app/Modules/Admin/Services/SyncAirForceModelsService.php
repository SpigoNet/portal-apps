<?php

namespace App\Modules\Admin\Services;

use App\Models\AiProvider;
use App\Modules\MundosDeMim\Models\AIProvider as AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncAirForceModelsService
{
    protected string $apiUrl = 'https://api.airforce/v1/models';

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
            $apiUrl = $this->provider?->sync_url ?: $this->apiUrl;

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
            Log::error('SyncAirForceModelsService Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erro ao buscar modelos: '.$e->getMessage(),
            ];
        }
    }

    protected function syncModel(array $modelData, array &$results): void
    {
        $modelId = $modelData['id'];

        $supportsImages = $modelData['supports_images'] ?? false;

        if (! $supportsImages) {
            return;
        }

        $driver = $this->provider?->driver ?: 'airforce';

        $supportsImageInput = $supportsImages;
        $supportsVideoOutput = false;

        $isFree = ($modelData['pricepermilliontokens'] ?? 0) == 0
            || str_ends_with($modelId, ':free')
            || ($modelData['multiplier'] ?? null) == 0;

        $existingModel = AiModel::where('model', $modelId)
            ->where('driver', $driver)
            ->first();

        $pricing = [
            'currency' => 'airforce',
            'pricePerMillionTokens' => (string) ($modelData['pricepermilliontokens'] ?? 0),
            'multiplier' => isset($modelData['multiplier']) ? (string) $modelData['multiplier'] : '1',
        ];

        $data = [
            'provider_id' => null,
            'name' => $this->formatName($modelId),
            'driver' => $driver,
            'model' => $modelId,
            'description' => "Model: {$modelId} | Owner: {$modelData['owned_by']} | Status: {$modelData['status']}",
            'supports_image_input' => $supportsImageInput,
            'supports_video_output' => $supportsVideoOutput,
            'is_active' => true,
            'pricing' => $pricing,
            'paid_only' => ! $isFree,
            'sort_order' => $existingModel?->sort_order ?? $this->getNextSortOrder($driver),
        ];

        if ($existingModel) {
            $existingModel->update($data);
            $results['updated']++;
        } else {
            AiModel::create($data);
            $results['created']++;
        }
    }

    protected function formatName(string $modelName): string
    {
        $name = str_replace(['-', '_', ':free'], [' ', ' ', ''], $modelName);

        return ucwords($name);
    }

    protected function getNextSortOrder(string $driver): int
    {
        return AiModel::max('sort_order') + 1;
    }
}

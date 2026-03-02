<?php

namespace App\Modules\Admin\Services;

use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPollinationModelsService
{
    protected string $apiUrl = 'https://gen.pollinations.ai/image/models';

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

            $models = $response->json();

            foreach ($models as $modelData) {
                try {
                    $this->syncModel($modelData, $results);
                } catch (\Exception $e) {
                    $results['errors'][] = "Error syncing model {$modelData['name']}: {$e->getMessage()}";
                    Log::error("Error syncing model {$modelData['name']}", ['error' => $e->getMessage()]);
                }
            }

            return [
                'success' => true,
                'message' => "Sincronizados: {$results['created']} criados, {$results['updated']} atualizados.",
                'details' => $results,
            ];

        } catch (\Exception $e) {
            Log::error('SyncPollinationModelsService Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Erro ao buscar modelos: '.$e->getMessage(),
            ];
        }
    }

    protected function syncModel(array $modelData, array &$results): void
    {
        $modelName = $modelData['name'];
        $driver = $this->provider?->driver ?: 'pollination';

        $supportsImage = in_array('image', $modelData['input_modalities'] ?? []);
        $supportsVideo = in_array('video', $modelData['output_modalities'] ?? []);

        $isPaid = $modelData['paid_only'] ?? false;

        $existingModel = AiModel::query()
            ->where('model', $modelName)
            ->where('driver', $driver)
            ->first();

        $pricing = $modelData['pricing'] ?? null;
        if ($pricing) {
            $pricing = $this->normalizePricing($pricing);
        }

        $data = [
            'provider_id' => $this->provider?->id,
            'name' => $this->formatName($modelName),
            'driver' => $driver,
            'model' => $modelName,
            'description' => $modelData['description'] ?? null,
            'supports_image_input' => $supportsImage,
            'supports_video_output' => $supportsVideo,
            'is_active' => true,
            'pricing' => $pricing,
            'paid_only' => $isPaid,
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

    protected function normalizePricing(array $pricing): array
    {
        foreach ($pricing as $key => $value) {
            if (is_numeric($value)) {
                $pricing[$key] = $this->formatNumber((float) $value);
            }
        }

        return $pricing;
    }

    protected function formatNumber(float $value): string
    {
        if ($value == 0) {
            return '0';
        }

        $absValue = abs($value);

        if ($absValue >= 0.0001) {
            return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
        }

        $formatted = sprintf('%.10f', $value);
        $formatted = rtrim($formatted, '0');

        return rtrim($formatted, '.');
    }

    protected function formatName(string $modelName): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $modelName));
    }

    protected function getNextSortOrder(string $driver): int
    {
        return AiModel::max('sort_order') + 1;
    }
}

<?php

namespace App\Modules\MundosDeMim\Services;

use App\Modules\MundosDeMim\Models\AIProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPollinationModelsService
{
    protected string $apiUrl = 'https://gen.pollinations.ai/image/models';

    public function sync(): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        try {
            $response = Http::timeout(30)->get($this->apiUrl);

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
        $driver = $this->detectDriver($modelName);

        $supportsImage = in_array('image', $modelData['input_modalities'] ?? []);
        $supportsVideo = in_array('video', $modelData['output_modalities'] ?? []);
        $paidOnly = $modelData['paid_only'] ?? false;

        $existingProvider = AIProvider::where('model', $modelName)->first();

        $data = [
            'name' => $this->formatName($modelName),
            'driver' => $driver,
            'model' => $modelName,
            'description' => $modelData['description'] ?? null,
            'supports_image_input' => $supportsImage,
            'supports_video_output' => $supportsVideo,
            'is_active' => true,
            'pricing' => $modelData['pricing'] ?? null,
            'paid_only' => $paidOnly,
            'sort_order' => $existingProvider?->sort_order ?? $this->getNextSortOrder($driver),
        ];

        if ($existingProvider) {
            $existingProvider->update($data);
            $results['updated']++;
        } else {
            AIProvider::create($data);
            $results['created']++;
        }
    }

    protected function detectDriver(string $modelName): string
    {
        return 'pollination';
    }

    protected function formatName(string $modelName): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $modelName));
    }

    protected function getNextSortOrder(string $driver): int
    {
        return AIProvider::max('sort_order') + 1;
    }
}

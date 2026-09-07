<?php

namespace App\Modules\Yomi\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Yomi\DTOs\ExternalCreator;
use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\MidiaTipo;
use App\Modules\Yomi\Enums\ProviderName;
use App\Modules\Yomi\Enums\StatusLeitura;
use App\Modules\Yomi\Exceptions\YomiSyncException;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\MangaExternalId;
use App\Modules\Yomi\Repositories\MangaRepository;
use App\Modules\Yomi\Services\ProgressService;
use App\Modules\Yomi\Services\SyncManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MangaApiController extends Controller
{
    private const MAX_LIMIT = 25;

    public function __construct(
        private readonly SyncManager $syncManager,
        private readonly ProgressService $progressService,
        private readonly MangaRepository $mangaRepository,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'module' => 'yomi',
            'api' => 'v1',
            'status' => 'up',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', Rule::enum(ProviderName::class)],
        ]);

        try {
            $results = $this->syncManager->search($data['q'], $this->provider($data['provider'] ?? null));
        } catch (YomiSyncException $exception) {
            return $this->providerError($exception);
        }

        return response()->json([
            'data' => array_map(fn (ExternalManga $manga): array => $this->externalToArray($manga), $results),
            'meta' => ['query' => $data['q'], 'count' => count($results)],
        ]);
    }

    public function populares(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['nullable', 'string', Rule::enum(ProviderName::class)],
        ]);
        $limit = $this->limit($request);

        try {
            $results = $this->syncManager->discover($limit, $this->provider($data['provider'] ?? null));
        } catch (YomiSyncException $exception) {
            return $this->providerError($exception);
        }

        return $this->listResponse($results, $limit);
    }

    public function recentes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['nullable', 'string', Rule::enum(ProviderName::class)],
        ]);
        $limit = $this->limit($request);

        try {
            $results = $this->syncManager->recent($limit, $this->provider($data['provider'] ?? null));
        } catch (YomiSyncException $exception) {
            return $this->providerError($exception);
        }

        return $this->listResponse($results, $limit);
    }

    public function externo(Request $request, string $provider, string $externalId): JsonResponse
    {
        if (ProviderName::tryFrom($provider) === null) {
            return response()->json(['message' => 'Provedor inválido.'], 422);
        }

        try {
            $result = $this->syncManager->getOrSync(externalId: $externalId, provider: $provider);
        } catch (YomiSyncException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }

        $manga = $this->loadRelations($result->manga);

        return response()->json([
            'data' => $this->localToArray($manga),
            'meta' => ['provider' => $provider, 'external_id' => $externalId, 'synced' => $result->synced],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $manga = Manga::find($id);

        if ($manga === null) {
            return response()->json(['message' => 'Obra não encontrada.'], 404);
        }

        $result = $this->syncManager->getOrSync($id);

        return response()->json([
            'data' => $this->localToArray($this->loadRelations($result->manga)),
            'meta' => ['from_cache' => $result->fromCache],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', Rule::enum(ProviderName::class)],
            'external_id' => ['required', 'string', 'max:64'],
        ]);

        $existing = $this->mangaRepository->findByExternalId($data['provider'], $data['external_id']);

        if ($existing !== null) {
            $this->progressService->setStatus($request->user()->id, $existing->id, StatusLeitura::PretendoLer);

            return response()->json([
                'data' => $this->localToArray($this->loadRelations($existing)),
                'meta' => ['created' => false],
            ]);
        }

        try {
            $result = $this->syncManager->getOrSync(externalId: $data['external_id'], provider: $data['provider']);
        } catch (YomiSyncException $exception) {
            return $this->providerError($exception);
        }

        $this->progressService->setStatus($request->user()->id, $result->manga->id, StatusLeitura::PretendoLer);

        return response()->json([
            'data' => $this->localToArray($this->loadRelations($result->manga)),
            'meta' => ['created' => true, 'provider' => $result->provider->value],
        ], 201);
    }

    /**
     * @param  array<int, ExternalManga>  $results
     */
    private function listResponse(array $results, int $limit): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (ExternalManga $manga): array => $this->externalToArray($manga), $results),
            'meta' => ['limit' => $limit, 'count' => count($results)],
        ]);
    }

    private function provider(?string $value): ?ProviderName
    {
        return $value !== null ? ProviderName::tryFrom($value) : null;
    }

    private function limit(Request $request): int
    {
        $limit = (int) $request->integer('limit', config('yomi.discover.limit', 12));

        return max(1, min($limit, self::MAX_LIMIT));
    }

    private function providerError(YomiSyncException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 502);
    }

    /**
     * @return array<string, mixed>
     */
    private function externalToArray(ExternalManga $external): array
    {
        $mappings = [];

        foreach ($external->externalIds as $id) {
            if (isset($id['provider'], $id['external_id'])) {
                $mappings[$id['provider']] = $id['external_id'];
            }
        }

        return [
            'id' => null,
            'titles' => [
                'romaji' => $external->title,
                'english' => null,
                'native' => $external->originalTitle,
            ],
            'alternative_titles' => $external->alternativeTitles,
            'synopsis' => $external->synopsis,
            'cover_url' => $external->imageUrl,
            'status' => $external->status,
            'format' => $external->type,
            'chapters' => $external->chapters,
            'volumes' => $external->volumes,
            'start_date' => $external->publishedFrom,
            'end_date' => $external->publishedTo,
            'genres' => $external->genres,
            'authors' => array_map(fn (ExternalCreator $creator): array => [
                'name' => $creator->name,
                'original_name' => $creator->originalName,
                'role' => $creator->role,
            ], $external->creators),
            'score' => $external->score,
            'age_rating' => $external->ageRating,
            'external_ids' => $mappings,
            'local' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function localToArray(Manga $manga): array
    {
        return [
            'id' => $manga->id,
            'titles' => [
                'principal' => $manga->titulo,
                'original' => $manga->titulo_original,
            ],
            'alternative_titles' => $manga->titulos->pluck('titulo')->all(),
            'synopsis' => $manga->sinopse,
            'cover_url' => $this->coverUrl($manga),
            'status' => $manga->status_publicacao,
            'format' => $manga->tipo,
            'chapters' => $manga->capitulos_conhecidos,
            'volumes' => $manga->volumes_conhecidos,
            'start_date' => $manga->data_inicio?->toDateString(),
            'end_date' => $manga->data_fim?->toDateString(),
            'genres' => $manga->generos->pluck('nome')->all(),
            'authors' => $manga->criadores->map(fn ($criador): array => [
                'name' => $criador->nome,
                'original_name' => $criador->nome_original,
                'role' => $criador->pivot->papel,
            ])->all(),
            'score' => $manga->nota_media,
            'age_rating' => $manga->classificacao_etaria,
            'external_ids' => $manga->externalIds->mapWithKeys(
                fn (MangaExternalId $id): array => [$id->provider => $id->external_id],
            )->all(),
            'source' => $manga->fonte_original,
            'local' => true,
        ];
    }

    private function loadRelations(Manga $manga): Manga
    {
        return $manga->load(['externalIds', 'titulos', 'generos', 'criadores', 'midias']);
    }

    private function coverUrl(Manga $manga): ?string
    {
        return $manga->midias->firstWhere('type', MidiaTipo::Capa->value)?->displayUrl();
    }
}

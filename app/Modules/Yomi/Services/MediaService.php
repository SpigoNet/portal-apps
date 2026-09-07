<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\DTOs\ExternalManga;
use App\Modules\Yomi\Enums\MidiaStatus;
use App\Modules\Yomi\Enums\MidiaTipo;
use App\Modules\Yomi\Exceptions\MediaDownloadException;
use App\Modules\Yomi\Jobs\DownloadCharacterImageJob;
use App\Modules\Yomi\Jobs\DownloadCreatorImageJob;
use App\Modules\Yomi\Jobs\DownloadMangaCoverJob;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\Midia;
use App\Modules\Yomi\Repositories\MidiaRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaService
{
    public function __construct(protected MidiaRepository $midiaRepository) {}

    public function registerExternal(string $entityType, int $entityId, string $type, ?string $sourceUrl): ?Midia
    {
        if ($sourceUrl === null || $sourceUrl === '') {
            return null;
        }

        $existing = Midia::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('type', $type)
            ->where('source_url', $sourceUrl)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Midia::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type,
            'source_url' => $sourceUrl,
            'status' => MidiaStatus::Pendente->value,
        ]);
    }

    public function enqueueMediaFor(Manga $manga, ExternalManga $external): void
    {
        $this->enqueueCover($manga, $external);

        $this->enqueueCharacterImages($manga, $external);

        $this->enqueueCreatorImages($manga, $external);
    }

    public function download(int $midiaId): Midia
    {
        $midia = Midia::findOrFail($midiaId);

        if ($midia->status === MidiaStatus::Baixada->value) {
            return $midia;
        }

        $this->midiaRepository->markDownloading($midia);

        try {
            $path = $this->fetchAndStore($midia);

            $checksum = $this->checksumOfStored($path);
            $existing = $this->midiaRepository->findByChecksum($checksum);

            if ($existing !== null && $existing->id !== $midia->id
                && $existing->storage_path !== null
                && Storage::disk($this->disk())->exists($existing->storage_path)) {
                $this->midiaRepository->markDownloaded(
                    $midia,
                    $existing->storage_path,
                    $existing->mime_type,
                    $existing->width,
                    $existing->height,
                    $checksum,
                );

                return $midia;
            }

            [$width, $height] = $this->dimensions(Storage::disk($this->disk())->path($path));
            $mimeType = $this->detectMime($midia, $path);

            $this->midiaRepository->markDownloaded($midia, $path, $mimeType, $width, $height, $checksum);

            return $midia;
        } catch (MediaDownloadException $exception) {
            $this->midiaRepository->markFailed($midia, $exception->getMessage());

            throw $exception;
        } catch (Throwable $throwable) {
            $this->midiaRepository->markFailed($midia, $throwable->getMessage());

            throw new MediaDownloadException('Falha no download da mídia: '.$throwable->getMessage(), previous: $throwable);
        }
    }

    private function fetchAndStore(Midia $midia): string
    {
        if (! filter_var($midia->source_url, FILTER_VALIDATE_URL)) {
            throw new MediaDownloadException('URL de origem inválida');
        }

        $timeout = (int) config('yomi.media.timeout', 30);
        $maxBytes = (int) config('yomi.media.max_size_mb', 20) * 1024 * 1024;
        $allowedMimes = (array) config('yomi.media.allowed_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ]);

        $response = Http::timeout($timeout)->get($midia->source_url);

        if (! $response->successful()) {
            throw new MediaDownloadException("Resposta HTTP inesperada ({$response->status()})");
        }

        $body = $response->body();

        if (strlen($body) > $maxBytes) {
            throw new MediaDownloadException("Arquivo excede o limite de {$maxBytes} bytes");
        }

        $mimeType = strtolower((string) explode(';', (string) $response->header('Content-Type', 'application/octet-stream'))[0]);

        if (! in_array($mimeType, $allowedMimes, true)) {
            throw new MediaDownloadException("Tipo MIME não permitido: {$mimeType}");
        }

        $extension = $this->extensionForMime($mimeType);
        $hash = substr(hash('sha1', $midia->source_url), 0, 16);
        $path = "yomi/{$midia->entity_type}/{$midia->type}/{$midia->entity_id}/{$hash}.{$extension}";

        Storage::disk($this->disk())->put($path, $body);

        return $path;
    }

    private function checksumOfStored(string $path): string
    {
        return (string) hash_file('sha256', Storage::disk($this->disk())->path($path));
    }

    private function detectMime(Midia $midia, string $path): ?string
    {
        $mime = (string) Storage::disk($this->disk())->mimeType($path);

        return $mime !== '' ? $mime : $midia->mime_type;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(string $localPath): array
    {
        if (! function_exists('getimagesize')) {
            return [null, null];
        }

        $info = @getimagesize($localPath);

        if ($info === false) {
            return [null, null];
        }

        return [$info[0], $info[1]];
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => 'img',
        };
    }

    private function disk(): string
    {
        return (string) config('yomi.media.disk', 'local');
    }

    public function dispatchDownloadJob(Midia $midia): void
    {
        match ($midia->entity_type) {
            'personagem' => DownloadCharacterImageJob::dispatch($midia->id),
            'criador' => DownloadCreatorImageJob::dispatch($midia->id),
            default => DownloadMangaCoverJob::dispatch($midia->id),
        };
    }

    private function enqueueCover(Manga $manga, ExternalManga $external): void
    {
        $midia = $this->registerExternal('manga', $manga->id, MidiaTipo::Capa->value, $external->imageUrl);

        if ($midia !== null) {
            $this->dispatchDownloadJob($midia);
        }
    }

    private function enqueueCharacterImages(Manga $manga, ExternalManga $external): void
    {
        $byName = [];

        foreach ($external->characters as $character) {
            $byName[strtolower($character->name)] = $character;
        }

        foreach ($manga->personagens as $personagem) {
            $externalCharacter = $byName[strtolower($personagem->nome)] ?? null;

            if ($externalCharacter === null) {
                continue;
            }

            $midia = $this->registerExternal(
                'personagem',
                $personagem->id,
                MidiaTipo::Personagem->value,
                $externalCharacter->imageUrl,
            );

            if ($midia !== null) {
                $this->dispatchDownloadJob($midia);
            }
        }
    }

    private function enqueueCreatorImages(Manga $manga, ExternalManga $external): void
    {
        $byName = [];

        foreach ($external->creators as $creator) {
            $byName[strtolower($creator->name)] = $creator;
        }

        foreach ($manga->criadores as $criador) {
            $externalCreator = $byName[strtolower($criador->nome)] ?? null;

            if ($externalCreator === null) {
                continue;
            }

            $midia = $this->registerExternal(
                'criador',
                $criador->id,
                MidiaTipo::Criador->value,
                $externalCreator->imageUrl,
            );

            if ($midia !== null) {
                $this->dispatchDownloadJob($midia);
            }
        }
    }
}

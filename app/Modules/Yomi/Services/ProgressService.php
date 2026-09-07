<?php

namespace App\Modules\Yomi\Services;

use App\Modules\Yomi\Enums\StatusLeitura;
use App\Modules\Yomi\Models\Capitulo;
use App\Modules\Yomi\Models\ProgressoUsuario;
use App\Modules\Yomi\Models\UsuarioCapitulo;
use App\Modules\Yomi\Repositories\ProgressoRepository;
use Illuminate\Support\Collection;

class ProgressService
{
    public function __construct(protected ProgressoRepository $progressoRepository) {}

    public function getForUser(int $userId, int $mangaId): ?ProgressoUsuario
    {
        return $this->progressoRepository->getForUser($userId, $mangaId);
    }

    public function library(int $userId, ?StatusLeitura $status = null): Collection
    {
        return ProgressoUsuario::query()
            ->where('user_id', $userId)
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->with(['manga'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function setStatus(int $userId, int $mangaId, StatusLeitura $status): ProgressoUsuario
    {
        $attributes = ['status' => $status->value];

        if ($status === StatusLeitura::Concluido && $this->getForUser($userId, $mangaId)?->completed_at === null) {
            $attributes['completed_at'] = now();
        }

        if ($status === StatusLeitura::PretendoLer) {
            $attributes['completed_at'] = null;
        }

        $progresso = $this->progressoRepository->updateOrCreate($userId, $mangaId, $attributes);

        if ($status === StatusLeitura::Lendo && $progresso->started_at === null) {
            $progresso->update(['started_at' => now()]);
        }

        return $progresso->fresh();
    }

    public function updateProgress(
        int $userId,
        int $mangaId,
        ?float $ultimoCapituloLido = null,
        ?int $ultimoVolumeLido = null,
    ): ProgressoUsuario {
        return $this->progressoRepository->updateOrCreate($userId, $mangaId, [
            'ultimo_capitulo_lido' => $ultimoCapituloLido,
            'ultimo_volume_lido' => $ultimoVolumeLido,
        ]);
    }

    public function rate(int $userId, int $mangaId, ?int $nota): ProgressoUsuario
    {
        return $this->progressoRepository->updateOrCreate($userId, $mangaId, [
            'nota' => $nota,
        ]);
    }

    public function toggleFavorite(int $userId, int $mangaId): ProgressoUsuario
    {
        $progresso = $this->getForUser($userId, $mangaId)
            ?? $this->progressoRepository->updateOrCreate($userId, $mangaId, ['status' => StatusLeitura::PretendoLer->value]);

        return $progresso->update(['favorito' => ! $progresso->favorito])
            ? $progresso->fresh()
            : $progresso;
    }

    public function setFavorite(int $userId, int $mangaId, bool $favorito): ProgressoUsuario
    {
        return $this->progressoRepository->updateOrCreate($userId, $mangaId, [
            'favorito' => $favorito,
        ]);
    }

    public function updateNotes(int $userId, int $mangaId, ?string $observacoes): ProgressoUsuario
    {
        return $this->progressoRepository->updateOrCreate($userId, $mangaId, [
            'observacoes' => $observacoes,
        ]);
    }

    public function markChapterRead(int $userId, int $mangaId, int $capituloId): UsuarioCapitulo
    {
        $capitulo = Capitulo::where('id', $capituloId)->where('manga_id', $mangaId)->firstOrFail();
        $numeroCapitulo = $capitulo->numero !== null ? (float) $capitulo->numero : null;

        return $this->progressoRepository->markChapterRead($userId, $mangaId, $capituloId, $numeroCapitulo);
    }

    public function unmarkChapterRead(int $userId, int $capituloId, ?int $mangaId = null): bool
    {
        return $this->progressoRepository->unmarkChapterRead($userId, $capituloId, $mangaId);
    }

    public function removeFromLibrary(int $userId, int $mangaId): bool
    {
        return $this->progressoRepository->removeProgressForUser($userId, $mangaId);
    }

    public function isChapterRead(int $userId, int $capituloId): bool
    {
        return $this->progressoRepository->isChapterRead($userId, $capituloId);
    }

    public function readChapters(int $userId, int $mangaId): Collection
    {
        return UsuarioCapitulo::query()
            ->where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->with(['capitulo'])
            ->orderBy('read_at')
            ->get();
    }
}

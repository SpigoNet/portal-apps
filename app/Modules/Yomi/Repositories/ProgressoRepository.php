<?php

namespace App\Modules\Yomi\Repositories;

use App\Modules\Yomi\Enums\StatusLeitura;
use App\Modules\Yomi\Models\ProgressoUsuario;
use App\Modules\Yomi\Models\UsuarioCapitulo;

class ProgressoRepository
{
    public function getForUser(int $userId, int $mangaId): ?ProgressoUsuario
    {
        return ProgressoUsuario::query()
            ->where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->first();
    }

    public function updateOrCreate(int $userId, int $mangaId, array $attributes): ProgressoUsuario
    {
        return ProgressoUsuario::updateOrCreate(
            ['user_id' => $userId, 'manga_id' => $mangaId],
            $attributes,
        );
    }

    public function markChapterRead(int $userId, int $mangaId, int $capituloId, ?float $numeroCapitulo): UsuarioCapitulo
    {
        $progresso = $this->getForUser($userId, $mangaId)
            ?? $this->updateOrCreate($userId, $mangaId, ['status' => StatusLeitura::Lendo->value]);

        if ($numeroCapitulo !== null
            && ($progresso->ultimo_capitulo_lido === null
                || $numeroCapitulo > $progresso->ultimo_capitulo_lido)) {
            $progresso->update(['ultimo_capitulo_lido' => $numeroCapitulo]);
        }

        $existing = UsuarioCapitulo::query()
            ->where('user_id', $userId)
            ->where('capitulo_id', $capituloId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return UsuarioCapitulo::create([
            'user_id' => $userId,
            'manga_id' => $mangaId,
            'capitulo_id' => $capituloId,
            'read_at' => now(),
        ]);
    }

    public function isChapterRead(int $userId, int $capituloId): bool
    {
        return UsuarioCapitulo::query()
            ->where('user_id', $userId)
            ->where('capitulo_id', $capituloId)
            ->exists();
    }

    public function unmarkChapterRead(int $userId, int $capituloId, ?int $mangaId = null): bool
    {
        $capitulo = \App\Modules\Yomi\Models\Capitulo::find($capituloId);

        $deleted = (bool) UsuarioCapitulo::query()
            ->where('user_id', $userId)
            ->where('capitulo_id', $capituloId)
            ->delete();

        if ($deleted && $capitulo !== null) {
            $mangaId = $mangaId ?? $capitulo->manga_id;
            $progresso = $this->getForUser($userId, $mangaId);

            if ($progresso !== null && $progresso->ultimo_capitulo_lido !== null && (float) $progresso->ultimo_capitulo_lido === (float) $capitulo->numero) {
                $maxRemaining = UsuarioCapitulo::query()
                    ->where('yomi_usuario_capitulos.user_id', $userId)
                    ->where('yomi_usuario_capitulos.manga_id', $mangaId)
                    ->join('yomi_capitulos', 'yomi_usuario_capitulos.capitulo_id', '=', 'yomi_capitulos.id')
                    ->max('yomi_capitulos.numero');

                $progresso->update(['ultimo_capitulo_lido' => $maxRemaining !== null ? (float) $maxRemaining : null]);
            }
        }

        return $deleted;
    }

    public function removeProgressForUser(int $userId, int $mangaId): bool
    {
        $removed = (bool) ProgressoUsuario::query()
            ->where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->delete();

        UsuarioCapitulo::query()
            ->where('user_id', $userId)
            ->where('manga_id', $mangaId)
            ->delete();

        return $removed;
    }
}

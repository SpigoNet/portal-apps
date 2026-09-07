<?php

namespace App\Modules\Yomi\Enums;

enum StatusPublicacao: string
{
    case Desconhecido = 'desconhecido';
    case Publicando = 'publicando';
    case Completo = 'completo';
    case Hiato = 'hiato';
    case Cancelado = 'cancelado';
    case NaoPublicado = 'nao_publicado';

    public function label(): string
    {
        return match ($this) {
            self::Desconhecido => 'Desconhecido',
            self::Publicando => 'Publicando',
            self::Completo => 'Completo',
            self::Hiato => 'Em hiato',
            self::Cancelado => 'Cancelado',
            self::NaoPublicado => 'Não publicado',
        };
    }

    public static function fromJikan(?string $status): self
    {
        return match ($status) {
            'Publishing' => self::Publicando,
            'Finished' => self::Completo,
            'On Hiatus' => self::Hiato,
            'Discontinued' => self::Cancelado,
            'Not yet published' => self::NaoPublicado,
            default => self::Desconhecido,
        };
    }

    public static function fromAniList(?string $status): self
    {
        return match ($status) {
            'RELEASING' => self::Publicando,
            'FINISHED' => self::Completo,
            'HIATUS' => self::Hiato,
            'CANCELLED' => self::Cancelado,
            'NOT_YET_RELEASED' => self::NaoPublicado,
            default => self::Desconhecido,
        };
    }

    public static function fromKitsu(?string $status): self
    {
        return match ($status) {
            'current' => self::Publicando,
            'finished' => self::Completo,
            'paused' => self::Hiato,
            'cancelled' => self::Cancelado,
            'upcoming' => self::NaoPublicado,
            'unreleased' => self::NaoPublicado,
            default => self::Desconhecido,
        };
    }

    public static function fromMal(?string $status): self
    {
        return match ($status) {
            'currently_publishing' => self::Publicando,
            'finished' => self::Completo,
            'yet_to_be_published' => self::NaoPublicado,
            default => self::Desconhecido,
        };
    }

    public static function fromMangaDex(?string $status): self
    {
        return match ($status) {
            'ongoing' => self::Publicando,
            'completed' => self::Completo,
            'hiatus', 'paused' => self::Hiato,
            'cancelled', 'aborted' => self::Cancelado,
            default => self::Desconhecido,
        };
    }
}

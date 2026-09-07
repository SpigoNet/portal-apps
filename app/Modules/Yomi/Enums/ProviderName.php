<?php

namespace App\Modules\Yomi\Enums;

enum ProviderName: string
{
    case Jikan = 'jikan';
    case AniList = 'anilist';
    case Kitsu = 'kitsu';
    case Mal = 'mal';
    case MangaDex = 'mangadex';

    public function label(): string
    {
        return match ($this) {
            self::Jikan => 'Jikan/MAL',
            self::AniList => 'AniList',
            self::Kitsu => 'Kitsu',
            self::Mal => 'MyAnimeList',
            self::MangaDex => 'MangaDex',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Jikan => 'API REST não oficial do MyAnimeList. Sem autenticação.',
            self::AniList => 'GraphQL com catálogo extenso. Token opcional para limites de requisição maiores.',
            self::Kitsu => 'API REST JSON:API da comunidade Kitsu. Sem autenticação.',
            self::Mal => 'API oficial do MyAnimeList. Requer um Client ID da aplicação.',
            self::MangaDex => 'API REST oficial da MangaDex. Sem autenticação para consultas públicas.',
        };
    }

    /**
     * Campos de credenciais exibidos nas configurações do módulo.
     *
     * @return array<string, array{label: string, required: bool, help: string}>
     */
    public function credentialFields(): array
    {
        return match ($this) {
            self::AniList => [
                'token' => [
                    'label' => 'Access Token (opcional)',
                    'required' => false,
                    'help' => 'Token de acesso do AniList. Aumenta o limite de requisições. Deixe vazio para consultas públicas.',
                ],
            ],
            self::Mal => [
                'client_id' => [
                    'label' => 'Client ID',
                    'required' => true,
                    'help' => 'ID da aplicação registrada em myanimelist.net/apiconfig. Enviado no header X-MAL-CLIENT-ID.',
                ],
            ],
            default => [],
        };
    }
}

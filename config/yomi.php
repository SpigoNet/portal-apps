<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administração
    |--------------------------------------------------------------------------
    |
    | ID do usuário com acesso exclusivo às configurações do módulo (visualização
    | e edição). Os demais usuários nem sequer visualizam a área.
    |
    */

    'owner_user_id' => (int) env('YOMI_OWNER_USER_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Sincronização
    |--------------------------------------------------------------------------
    |
    | Define a política de atualização dos dados locais a partir dos provedores
    | externos. O banco local é a fonte operacional primária.
    |
    */

    'sync' => [
        'stale_after_minutes' => env('YOMI_STALE_AFTER_MINUTES', 1440),
        'next_sync_after_minutes' => env('YOMI_NEXT_SYNC_AFTER_MINUTES', 1440),
        'refresh_batch_size' => (int) env('YOMI_REFRESH_BATCH_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Descoberta
    |--------------------------------------------------------------------------
    |
    | Quantidade de obras em destaque exibidas por padrão na seção "Descobrir"
    | quando nenhuma busca é informada.
    |
    */

    'discover' => [
        'limit' => (int) env('YOMI_DISCOVER_LIMIT', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prioridade de provedores
    |--------------------------------------------------------------------------
    |
    | Ordem em que os provedores externos são consultados (primário → fallback).
    | Ajustável por ambiente via YOMI_PROVIDER_PRIORITY (separado por vírgula).
    |
    */

    'provider_priority' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('YOMI_PROVIDER_PRIORITY', 'jikan,anilist')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Filas
    |--------------------------------------------------------------------------
    */

    'queues' => [
        'sync' => env('YOMI_QUEUE_SYNC', 'yomi-sync'),
        'media' => env('YOMI_QUEUE_MEDIA', 'yomi-media'),
        'maintenance' => env('YOMI_QUEUE_MAINTENANCE', 'yomi-maintenance'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provedores externos
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'jikan' => [
            'enabled' => env('YOMI_JIKAN_ENABLED', true),
            'base_url' => env('YOMI_JIKAN_BASE_URL', 'https://api.jikan.moe/v4'),
            'timeout' => (int) env('YOMI_JIKAN_TIMEOUT', 15),
            'throttle_ms' => (int) env('YOMI_JIKAN_THROTTLE_MS', 400),
            'retry' => [
                'tries' => (int) env('YOMI_JIKAN_RETRY_TRIES', 3),
                'backoff_ms' => (int) env('YOMI_JIKAN_RETRY_BACKOFF_MS', 1000),
            ],
        ],
        'anilist' => [
            'enabled' => env('YOMI_ANILIST_ENABLED', true),
            'base_url' => env('YOMI_ANILIST_BASE_URL', 'https://graphql.anilist.co'),
            'timeout' => (int) env('YOMI_ANILIST_TIMEOUT', 15),
            'throttle_ms' => (int) env('YOMI_ANILIST_THROTTLE_MS', 100),
            'retry' => [
                'tries' => (int) env('YOMI_ANILIST_RETRY_TRIES', 3),
                'backoff_ms' => (int) env('YOMI_ANILIST_RETRY_BACKOFF_MS', 1000),
            ],
            'credentials' => [
                'token' => env('YOMI_ANILIST_TOKEN'),
            ],
        ],
        'kitsu' => [
            'enabled' => env('YOMI_KITSU_ENABLED', false),
            'base_url' => env('YOMI_KITSU_BASE_URL', 'https://kitsu.io/api/edge'),
            'timeout' => (int) env('YOMI_KITSU_TIMEOUT', 15),
            'throttle_ms' => (int) env('YOMI_KITSU_THROTTLE_MS', 200),
            'retry' => [
                'tries' => (int) env('YOMI_KITSU_RETRY_TRIES', 3),
                'backoff_ms' => (int) env('YOMI_KITSU_RETRY_BACKOFF_MS', 1000),
            ],
        ],
        'mal' => [
            'enabled' => env('YOMI_MAL_ENABLED', false),
            'base_url' => env('YOMI_MAL_BASE_URL', 'https://api.myanimelist.net/v2'),
            'timeout' => (int) env('YOMI_MAL_TIMEOUT', 15),
            'throttle_ms' => (int) env('YOMI_MAL_THROTTLE_MS', 300),
            'retry' => [
                'tries' => (int) env('YOMI_MAL_RETRY_TRIES', 3),
                'backoff_ms' => (int) env('YOMI_MAL_RETRY_BACKOFF_MS', 1000),
            ],
            'credentials' => [
                'client_id' => env('YOMI_MAL_CLIENT_ID'),
            ],
        ],
        'mangadex' => [
            'enabled' => env('YOMI_MANGADEX_ENABLED', false),
            'base_url' => env('YOMI_MANGADEX_BASE_URL', 'https://api.mangadex.org'),
            'timeout' => (int) env('YOMI_MANGADEX_TIMEOUT', 15),
            'throttle_ms' => (int) env('YOMI_MANGADEX_THROTTLE_MS', 200),
            'retry' => [
                'tries' => (int) env('YOMI_MANGADEX_RETRY_TRIES', 3),
                'backoff_ms' => (int) env('YOMI_MANGADEX_RETRY_BACKOFF_MS', 1000),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Armazenamento de mídia
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => env('YOMI_MEDIA_DISK', 'local'),
        'timeout' => (int) env('YOMI_MEDIA_TIMEOUT', 30),
        'max_size_mb' => (int) env('YOMI_MEDIA_MAX_SIZE_MB', 20),
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ],
    ],
];

<?php

return [
    'api' => [
        'base_url' => env('QURAN_API_BASE_URL'),
        'oauth_url' => env('QURAN_OAUTH_URL'),
    ],

    'credentials' => [
        'client_id' => env('QURAN_CLIENT_ID'),
        'client_secret' => env('QURAN_CLIENT_SECRET'),
    ],

    'endpoints' => [
        'chapters' => '/content/api/v4/chapters',
        'chapter' => '/content/api/v4/chapters/{id}',
        'chapter_info' => '/content/api/v4/chapters/{id}/info',
    ],

    'cache' => [
        'token_ttl' => env('QURAN_CACHE_TOKEN_TTL', 3500),
        'chapters_ttl' => env('QURAN_CACHE_CHAPTERS_TTL', 86400),
        'chapter_ttl' => env('QURAN_CACHE_CHAPTER_TTL', 86400),
        'chapter_info_ttl' => env('QURAN_CACHE_CHAPTER_INFO_TTL', 86400),
    ],

    'default_language' => env('QURAN_DEFAULT_LANGUAGE', 'en'),
];

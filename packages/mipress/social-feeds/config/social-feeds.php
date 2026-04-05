<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aktivní provideři
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'facebook' => [
            'enabled' => env('SOCIAL_FACEBOOK_ENABLED', false),
            'stateless' => env('SOCIAL_FACEBOOK_STATELESS', false),
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v23.0'),
            'scopes' => array_filter(explode(',', env('FACEBOOK_SCOPES', 'pages_show_list,pages_read_engagement,pages_read_user_content'))),
        ],
        'instagram' => [
            'enabled' => env('SOCIAL_INSTAGRAM_ENABLED', false),
            'client_id' => env('INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default_ttl' => env('SOCIAL_FEEDS_CACHE_TTL', 3600),
        'store' => env('SOCIAL_FEEDS_CACHE_STORE'),
        'prefix' => 'social-feed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh
    |--------------------------------------------------------------------------
    */
    'refresh' => [
        'queue' => env('SOCIAL_FEEDS_QUEUE', 'default'),
        'schedule' => env('SOCIAL_FEEDS_SCHEDULE', 'hourly'),
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Obrázky
    |--------------------------------------------------------------------------
    */
    'images' => [
        'proxy_enabled' => env('SOCIAL_FEEDS_PROXY_IMAGES', false),
        'storage_disk' => env('SOCIAL_FEEDS_STORAGE_DISK', 'public'),
        'storage_path' => 'social-feeds/images',
    ],
];

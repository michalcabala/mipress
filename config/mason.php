<?php

declare(strict_types=1);

return [
    'generator' => [
        'namespace' => 'App\\Mason',
        'views_path' => 'mason',
    ],
    'preview' => [
        'layout' => 'layouts.mason-preview',
    ],
    'entry' => [
        'layout' => 'layouts.mason-entry',
    ],
    'routes' => [
        'middleware' => ['web', 'auth'],
    ],
];

<?php

use MoonShine\Laravel\Layouts\CompactLayout;

return [
    'dir' => 'app/MoonShine',
    'namespace' => 'App\MoonShine',

    'title' => env('MOONSHINE_TITLE', 'MoonShine'),
    'logo' => env('MOONSHINE_LOGO'),
    'logo_small' => env('MOONSHINE_LOGO_SMALL'),

    'layout' => CompactLayout::class,

    'disk' => 'public',

    'cache' => 'file',

    'assets' => [
        'js' => [],
        'css' => [],
    ],

    'forms' => [
        'inline_errors' => false,
    ],

    'pages' => [
        'dashboard' => App\MoonShine\Pages\Dashboard::class,
    ],

    'use_migrations' => true,
    'use_notifications' => true,
    'use_database_notifications' => true,

    'auth' => [
        'enable' => true,
        'middleware' => ['moonshine'],
        'guard' => 'moonshine',
        'pipelines' => [],
    ],

    'locales' => ['en', 'ru'],

    'global_search' => true,

    'tinymce' => [
        'token' => env('TINYMCE_TOKEN', ''),
        'version' => env('TINYMCE_VERSION', '6'),
    ],
];

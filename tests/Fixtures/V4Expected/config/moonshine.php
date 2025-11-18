<?php

use MoonShine\Laravel\Layouts\AppLayout;

return [
    'dir' => 'app/MoonShine',
    'namespace' => 'App\MoonShine',

    'title' => env('MOONSHINE_TITLE', 'MoonShine'),
    'logo' => env('MOONSHINE_LOGO'),
    'logo_small' => env('MOONSHINE_LOGO_SMALL'),

    'layout' => AppLayout::class,

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
        'enabled' => true,
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

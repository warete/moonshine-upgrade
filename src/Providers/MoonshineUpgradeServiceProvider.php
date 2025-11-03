<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Providers;

use Illuminate\Support\ServiceProvider;

final class MoonshineUpgradeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'moonshine-upgrade');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-upgrade');

        $this->publishes([
            __DIR__ . '/../../config/moonshine-upgrade.php' => config_path('moonshine-upgrade.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/moonshine-upgrade.php',
            'moonshine-upgrade'
        );

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('warete/moonshine-upgrade'),
        ]);

        $this->commands([]);
    }
}

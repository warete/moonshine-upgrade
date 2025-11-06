<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Providers;

use Illuminate\Support\ServiceProvider;
use Warete\MoonshineUpgrade\Commands\UpgradeCommand;

final class MoonshineUpgradeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'moonshine-upgrade');

        $this->publishes([
            __DIR__ . '/../../rector-upgrade.php' => base_path('rector-upgrade.php'),
        ]);

        $this->commands([
            UpgradeCommand::class,
        ]);
    }
}

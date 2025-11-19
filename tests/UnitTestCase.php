<?php

namespace Warete\MoonshineUpgrade\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider;

/**
 * Lightweight test case for tests that don't require full MoonShine setup.
 * Use this for fixture validation, Rector rule tests, and file structure tests.
 */
abstract class UnitTestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonshineUpgradeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', true);
    }
}

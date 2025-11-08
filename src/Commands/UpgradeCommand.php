<?php

namespace Warete\MoonshineUpgrade\Commands;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

use MoonShine\Laravel\Commands\MoonShineCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Warete\MoonshineUpgrade\VersionStrategies\Factory;

#[AsCommand('moonshine:upgrade')]
class UpgradeCommand extends MoonShineCommand
{
    protected Factory $upgradeFactory;

    protected $signature = 'moonshine:upgrade {version=4} {--dry-run} {--dir=}';

    protected $description = 'Upgrade moonshine';

    public function handle(Factory $upgradeFactory): int
    {
        $this->upgradeFactory = $upgradeFactory;

        $version = (int)$this->argument('version');

        $isDryRun = (bool)$this->option('dry-run');

        $dir = match ($this->option('dir')) {
            '.', '/', './' => '',
            default => $this->option('dir'),
        };
        $baseDir = base_path($dir);

        intro("Starting MoonShine upgrade to version {$version}");

        info("Running in directory {$baseDir}");

        confirm('Are you sure you want to upgrade moonshine? This operation can change your files. Please make backup before continuing.', default: false, required: true);


        $versionStrategy = $this->upgradeFactory->getByVersion($version, $isDryRun, $baseDir, $this);

        $versionStrategy();

        outro("Your MoonShine app was successfully upgraded to v{$version}! Now you can upgrade MoonShine version in composer.json");

        return self::SUCCESS;
    }
}

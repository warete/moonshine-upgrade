<?php

namespace Warete\MoonshineUpgrade\Commands;

use MoonShine\Laravel\Commands\MoonShineCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Warete\MoonshineUpgrade\VersionStrategies\Factory;

#[AsCommand('moonshine:upgrade')]
class UpgradeCommand extends MoonShineCommand
{
    public function __construct(protected Factory $upgradeFactory)
    {
        parent::__construct();
    }

    protected $signature = 'moonshine:upgrade {version=4} {--dry-run}';

    protected $description = 'Upgrade moonshine';

    public function handle(): int
    {
        $version = (int)$this->argument('version');

        $isDryRun = (bool)$this->option('dry-run');

        $versionStrategy = $this->upgradeFactory->getByVersion($version, $isDryRun, $this);

        $versionStrategy();

        return self::SUCCESS;
    }
}

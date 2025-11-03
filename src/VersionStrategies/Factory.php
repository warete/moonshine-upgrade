<?php

namespace Warete\MoonshineUpgrade\VersionStrategies;

use Illuminate\Console\Command;
use RuntimeException;

class Factory
{
    public function getByVersion(int $version, bool $isDryRun, ?Command $command = null): VersionStrategy
    {
        return match ($version) {
            4 => new V4($isDryRun, $command),
            default => throw new RuntimeException('Provide supported version'),
        };
    }
}

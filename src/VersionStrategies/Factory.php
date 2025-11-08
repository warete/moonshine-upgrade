<?php

namespace Warete\MoonshineUpgrade\VersionStrategies;

use Illuminate\Console\Command;
use RuntimeException;

class Factory
{
    public function getByVersion(int $version, bool $isDryRun, string $baseDir,  ?Command $command = null): VersionStrategy
    {
        return match ($version) {
            4 => new V4($isDryRun, $baseDir, $command),
            default => throw new RuntimeException('Provide supported version'),
        };
    }
}

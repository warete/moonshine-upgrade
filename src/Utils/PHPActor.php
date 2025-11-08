<?php

namespace Warete\MoonshineUpgrade\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class PHPActor
{
    protected string $PHPActorPath;

    public function __construct()
    {
        $this->PHPActorPath = base_path('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpactor.phar');
    }

    public function moveClass(string $from, string $to): void
    {
        $this->checkAndDownloadExecutable();

        $process = Process::command(
            "php {$this->PHPActorPath} class:move -n {$from} {$to}"
        );
        $process->timeout(60);

        $processOutput = $process->run();

        if (! $processOutput->successful()) {
            throw new RuntimeException(\sprintf('Failed to move class `%s` to `%s`: %s', $from, $to, $processOutput->errorOutput()));
        }
    }

    protected function checkAndDownloadExecutable(): void
    {

        if (File::exists($this->PHPActorPath)) {
            return;
        }

        $process = Process::command(
            "curl -Lo {$this->PHPActorPath} https://github.com/phpactor/phpactor/releases/latest/download/phpactor.phar"
        );
        $processOutput = $process->run();

        if (! $processOutput->successful()) {
            throw new RuntimeException('Failed to download executable PHPActor: ' . $processOutput->errorOutput());
        }

        chmod($this->PHPActorPath, 0755);
    }
}

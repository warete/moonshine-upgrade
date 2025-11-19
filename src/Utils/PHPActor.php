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

        $process = Process::command([
            PHP_BINARY,
            $this->PHPActorPath,
            'class:move',
            '-n',
            $from,
            $to,
        ]);
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

        // Ensure directory exists
        $dir = dirname($this->PHPActorPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Download using file_get_contents for cross-platform compatibility
        $url = 'https://github.com/phpactor/phpactor/releases/latest/download/phpactor.phar';
        $content = @file_get_contents($url);

        if ($content === false) {
            throw new RuntimeException('Failed to download executable PHPActor from ' . $url);
        }

        if (File::put($this->PHPActorPath, $content) === false) {
            throw new RuntimeException('Failed to save executable PHPActor to ' . $this->PHPActorPath);
        }

        // chmod only works on Unix-like systems
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($this->PHPActorPath, 0755);
        }
    }
}

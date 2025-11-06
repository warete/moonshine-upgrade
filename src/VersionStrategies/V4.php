<?php

namespace Warete\MoonshineUpgrade\VersionStrategies;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Core\Resources\Resources;
use MoonShine\Core\Traits\WithCore;
use ReflectionClass;
use Throwable;
use Warete\MoonshineUpgrade\Utils\PHPActor;

class V4 implements VersionStrategy
{
    use WithCore;

    public function __construct(
        protected bool $isDryRun,
        protected ?Command $command = null,
    ) {
        $this->core = $this->getCore();
    }

    public function __invoke(): void
    {
        $basePath = base_path();
        $this->command?->info('Starting upgrade by rector');
        $process = Process::command(
            "./vendor/bin/rector --config {$basePath}/rector-upgrade.php --clear-cache -vv"
        );
        $process->timeout(120);
        $processOutput = $process->run();
        if ($processOutput->successful()) {
            $this->command?->info('Successfully upgraded by rector');
        } else {
            $this->command?->error(\sprintf('Failed to upgrade by rector: %s', $processOutput->output()));

            return;
        }

        /** @var Resources $resources */
        $resources = $this->core->getResources();

        foreach ($resources as $resource) {
            $this->command?->info("Upgrading resource: {$resource->getTitle()}");
            $this->upgradeResource($resource);
        }
    }

    protected function upgradeResource(ResourceContract $resource): void
    {
        $rResource = new ReflectionClass($resource);
        $resourceName = str($rResource->getShortName())
            ->ucfirst()
            ->remove('resource', false)
            ->value();

        $classFilePath = $rResource->getFileName();
        $classFileName = basename($classFilePath);
        $classDir = dirname($rResource->getFileName());
        $newClassDir = $classDir . DIRECTORY_SEPARATOR . $resourceName;
        $newClassPath = $newClassDir . DIRECTORY_SEPARATOR . $classFileName;

        $this->command?->info("Starting move class {$classFilePath}");
        $moveResult = $this->moveClass($classFilePath, $newClassPath);

        if ($moveResult) {
            $this->command?->info("Moved resource: {$resourceName}");
        } else {
            $this->command?->error("Failed to move resource: {$resourceName}");
        }

        foreach ($resource->getPages() as $page) {
            $this->upgradePage($page, $newClassDir);
        }
    }

    protected function upgradePage(PageContract $page, string $resourceDir): void
    {
        $rPage = new ReflectionClass($page);
        $pageName = str($rPage->getShortName())
            ->ucfirst()
            ->remove('resource', false)
            ->value();
        $pageFullNameOld = $rPage->getName();
        $classFilePath = $rPage->getFileName();
        $classFileName = basename($classFilePath);
        $newClassPath = $resourceDir . DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR . $classFileName;
        if (str_starts_with($pageFullNameOld, 'MoonShine\\Laravel\\Pages\\Crud')) {
            return;
        }

        $moveResult = $this->moveClass($classFilePath, $newClassPath);
        if ($moveResult) {
            $this->command?->info("Moved page: {$pageName}");
        } else {
            $this->command?->error("Failed to move page: {$pageName}");
        }
    }

    protected function moveClass(string $from, string $to): bool
    {
        try {
            (new PHPActor())->moveClass($from, $to);

            return true;
        } catch (Throwable $e) {
            $this->command?->error(\sprintf('Failed to move class `%s`: %s', $from, $e->getMessage()));

            return false;
        }
    }
}

<?php

namespace Warete\MoonshineUpgrade\VersionStrategies;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;

use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Core\Resources\Resources;
use MoonShine\Core\Traits\WithCore;
use ReflectionClass;
use Throwable;
use Warete\MoonshineUpgrade\Utils\PHPActor;

/**
 * @property ?CoreContract $core
 */
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
        $processOutput = spin(function () {
            $basePath = base_path();
            $rectorDryRun = $this->isDryRun ? ' --dry-run || exit 0' : '';
            $process = Process::command(
                "./vendor/bin/rector --config {$basePath}/rector-upgrade.php --clear-cache -vv{$rectorDryRun}"
            );
            $process->timeout(120);

            return $process->run();
        }, 'Upgrade by rector in progress');
        if ($processOutput->successful()) {
            info('Successfully upgraded by rector');
        } else {
            $this->command?->fail(\sprintf('Failed to upgrade by rector: %s', $processOutput->output()));
        }

        /** @var Resources $resources */
        $resources = $this->core->getResources();

        info('Upgrading resources and pages');
        progress('Upgrading resources', $resources, function (ResourceContract $resource, \Laravel\Prompts\Progress $progress): void {
            $progress
                ->label("Upgrading resource: {$resource->getTitle()}");
            $this->upgradeResource($resource, $progress);
        });
    }

    protected function upgradeResource(ResourceContract $resource, \Laravel\Prompts\Progress $progress): void
    {
        $rResource = new ReflectionClass($resource);
        $resourceName = str($rResource->getShortName())
            ->ucfirst()
            ->remove('resource', false)
            ->value();

        $classFilePath = $rResource->getFileName();
        $classFileName = basename($classFilePath);
        $classDir = dirname($rResource->getFileName());
        $classDirName = basename($classDir);

        if ($classDirName == $resourceName) {
            $progress->hint("[{$resourceName}] Resource already upgraded");
            $newClassDir = $classDir;
        } else {
            $newClassDir = $classDir . DIRECTORY_SEPARATOR . $resourceName;
            $newClassPath = $newClassDir . DIRECTORY_SEPARATOR . $classFileName;

            $progress->hint("[{$resourceName}] Starting move resource class");
            $moveResult = $this->moveClass($classFilePath, $newClassPath);

            if ($moveResult) {
                $progress->hint("[{$resourceName}] Resource was moved");
            } else {
                $this->command?->fail("\t[{$resourceName}] Failed to move resource");
            }
        }

        $progress->hint("[{$resourceName}] Starting upgrade resource pages");
        foreach ($resource->getPages() as $page) {
            $this->upgradePage($page, $newClassDir, $progress);
        }
    }

    protected function upgradePage(PageContract $page, string $resourceDir, \Laravel\Prompts\Progress $progress): void
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

        if ($newClassPath == $classFilePath) {
            $progress->hint("[{$pageName}] Page already upgraded");
            return;
        }

        $progress->hint("[{$pageName}] Starting move page class");
        $moveResult = $this->moveClass($classFilePath, $newClassPath);
        if ($moveResult) {
            $progress->hint("[{$pageName}] Page was moved");
        } else {
            $moveError = $moveResult->output();
            $this->command?->fail("[{$pageName}] Failed to move page: {$moveError}");
        }
    }

    protected function moveClass(string $from, string $to): bool
    {
        try {
            if ($this->isDryRun) {
                return true;
            }
            (new PHPActor())->moveClass($from, $to);

            return true;
        } catch (Throwable $e) {
            $this->command?->fail(\sprintf('Failed to move class `%s`: %s', $from, $e->getMessage()));

            return false;
        }
    }
}

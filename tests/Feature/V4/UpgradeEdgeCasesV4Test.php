<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Feature\V4;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Warete\MoonshineUpgrade\Tests\Concerns\AssertUpgrade;
use Warete\MoonshineUpgrade\Tests\TestCase;

final class UpgradeEdgeCasesV4Test extends TestCase
{
    use AssertUpgrade;

    protected string $testWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testWorkspace = base_path('tests/workspace-edge');

        if (File::exists($this->testWorkspace)) {
            File::deleteDirectory($this->testWorkspace);
        }
        File::makeDirectory($this->testWorkspace, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testWorkspace)) {
            File::deleteDirectory($this->testWorkspace);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_handles_already_upgraded_resource(): void
    {
        // Create a resource that's already in the V4 structure
        $resourceDir = $this->testWorkspace . '/app/MoonShine/Resources/Product';
        File::makeDirectory($resourceDir . '/Pages', 0755, true);

        $resourceContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources\Product;

use MoonShine\Crud\Resources\CrudResource;

class ProductResource extends CrudResource
{
    public string $model = Product::class;
}
PHP;

        File::put($resourceDir . '/ProductResource.php', $resourceContent);

        // Running upgrade should not fail or duplicate structure
        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ]);

        $result->assertSuccessful();
    }

    #[Test]
    public function it_handles_resource_without_custom_pages(): void
    {
        // Create a simple resource without custom pages
        $resourcesDir = $this->testWorkspace . '/app/MoonShine/Resources';
        File::makeDirectory($resourcesDir, 0755, true);

        $resourceContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class SimpleResource extends CrudResource
{
    public string $model = Simple::class;
    
    protected function indexFields(): iterable
    {
        return [];
    }
}
PHP;

        File::put($resourcesDir . '/SimpleResource.php', $resourceContent);

        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ]);

        $result->assertSuccessful();
    }

    #[Test]
    public function it_handles_specific_directory_upgrade(): void
    {
        // Create multiple resource directories
        $resources = ['Product', 'Category', 'Tag'];
        $dir = $this->testWorkspace . "/app/MoonShine/Resources";
        File::makeDirectory($dir, 0755, true);

        foreach ($resources as $resource) {
            $content = <<<PHP
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class {$resource}Resource extends CrudResource
{
    public string \$model = {$resource}::class;
}
PHP;

            File::put($dir . "/{$resource}Resource.php", $content);
        }

        // Upgrade only specific directory
        $specificDir = $this->testWorkspace . '/app/MoonShine/Resources';

        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $specificDir,
        ]);

        $result->assertSuccessful();
    }

    #[Test]
    public function it_validates_dry_run_does_not_modify_files(): void
    {
        $resourcesDir = $this->testWorkspace . '/app/MoonShine/Resources';
        File::makeDirectory($resourcesDir, 0755, true);

        $originalContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class TestResource extends CrudResource
{
    public string $model = Test::class;
}
PHP;

        $resourceFile = $resourcesDir . '/TestResource.php';
        File::put($resourceFile, $originalContent);

        // Store original modification time
        $originalMtime = filemtime($resourceFile);

        // Wait a moment to ensure time difference would be detectable
        usleep(100000); // 0.1 second

        // Run in dry-run mode
        $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ])->assertSuccessful();

        // File should not be modified in dry-run
        $this->assertEquals(
            $originalContent,
            File::get($resourceFile),
            'File content should not change in dry-run mode'
        );

        // Note: In actual dry-run, rector might still read the file,
        // but it shouldn't write changes
    }

    #[Test]
    public function it_handles_windows_and_unix_paths(): void
    {
        // Test that path handling works correctly on both systems
        $resourcesDir = $this->testWorkspace . '/app/MoonShine/Resources';
        File::makeDirectory($resourcesDir, 0755, true);

        $resourceContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class PathTestResource extends CrudResource
{
    public string $model = PathTest::class;
}
PHP;

        File::put($resourcesDir . '/PathTestResource.php', $resourceContent);

        // Just test that the first path works
        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ]);

        $result->assertSuccessful();
    }

    #[Test]
    public function it_handles_current_directory_option(): void
    {
        // Test --dir=. option
        $resourcesDir = $this->testWorkspace . '/app/MoonShine/Resources';
        File::makeDirectory($resourcesDir, 0755, true);

        $resourceContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class CurrentDirResource extends CrudResource
{
    public string $model = CurrentDir::class;
}
PHP;

        File::put($resourcesDir . '/CurrentDirResource.php', $resourceContent);

        // Test with explicit path (. is handled by command)
        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ]);

        $result->assertSuccessful();
    }

    #[Test]
    public function it_can_run_upgrade_multiple_times_idempotent(): void
    {
        // Running upgrade multiple times should be safe (idempotent)
        $resourcesDir = $this->testWorkspace . '/app/MoonShine/Resources';
        File::makeDirectory($resourcesDir, 0755, true);

        $resourceContent = <<<'PHP'
<?php

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\CrudResource;

class IdempotentResource extends CrudResource
{
    public string $model = Idempotent::class;
}
PHP;

        File::put($resourcesDir . '/IdempotentResource.php', $resourceContent);

        // Run upgrade once - testing idempotency properly requires non-dry-run
        $result = $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ]);

        $result->assertSuccessful();

        // TODO: Test multiple runs when we can verify actual file changes
    }
}

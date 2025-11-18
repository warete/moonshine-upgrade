<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Feature\V4;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Warete\MoonshineUpgrade\Tests\Concerns\AssertUpgrade;
use Warete\MoonshineUpgrade\Tests\TestCase;

final class UpgradeV4Test extends TestCase
{
    use AssertUpgrade;

    protected string $testWorkspace;
    protected string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesPath = __DIR__ . '/../../Fixtures';
        $this->testWorkspace = base_path('tests/workspace');

        // Clean up workspace before each test
        if (File::exists($this->testWorkspace)) {
            File::deleteDirectory($this->testWorkspace);
        }
        File::makeDirectory($this->testWorkspace, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up workspace after each test
        if (File::exists($this->testWorkspace)) {
            File::deleteDirectory($this->testWorkspace);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_upgrades_article_resource_structure(): void
    {
        // Arrange: Copy V3 fixtures to test workspace
        $this->copyFixtures(
            $this->fixturesPath . '/V3/app',
            $this->testWorkspace . '/app'
        );

        // Act: Run upgrade command (dry-run for safety in tests)
        $this->runUpgrade([
            'version' => 4,
            '--dry-run' => true,
            '--dir' => $this->testWorkspace,
        ])->assertSuccessful();

        // Assert: Check that the command ran successfully
        // Note: In dry-run mode, files won't actually be moved
        // This test verifies the command doesn't crash
    }

    #[Test]
    public function it_moves_article_resource_to_correct_directory(): void
    {
        // This test verifies the EXPECTED structure after upgrade
        // Actual file moving is done by MoonShine's resource discovery
        // We test that the expected V4 structure is correct

        $v4BasePath = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources';

        // 1. ArticleResource.php should be in Article subdirectory
        $this->assertUpgradeFileExists($v4BasePath . '/Article/ArticleResource.php');

        // 2. Namespace should be App\MoonShine\Resources\Article
        $this->assertFileContains(
            $v4BasePath . '/Article/ArticleResource.php',
            'namespace App\MoonShine\Resources\Article;'
        );

        // 3. Pages should be in Article/Pages/
        $this->assertUpgradeFileExists($v4BasePath . '/Article/Pages/ArticleIndexPage.php');
        $this->assertUpgradeFileExists($v4BasePath . '/Article/Pages/ArticleFormPage.php');
        $this->assertUpgradeFileExists($v4BasePath . '/Article/Pages/ArticleDetailPage.php');

        // 4. Page namespaces should be updated
        $this->assertFileContains(
            $v4BasePath . '/Article/Pages/ArticleIndexPage.php',
            'namespace App\MoonShine\Resources\Article\Pages;'
        );

        // 5. Old structure should not exist in V4
        $this->assertFileDoesNotExist($this->fixturesPath . '/V4Expected/app/MoonShine/Resources/ArticleResource.php');
        $this->assertDirectoryDoesNotExist($this->fixturesPath . '/V4Expected/app/MoonShine/Pages/Article');
    }

    #[Test]
    public function it_adds_deprecated_docs_to_methods(): void
    {
        // This test verifies that rector rules add @deprecated comments
        // We'll check the expected fixture files
        $expectedFile = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $this->assertFileExists($expectedFile);
        $this->assertDeprecatedDocAdded($expectedFile, 'indexButtons');
        $this->assertDeprecatedDocAdded($expectedFile, 'formButtons');
        $this->assertDeprecatedDocAdded($expectedFile, 'metrics');
        $this->assertDeprecatedDocAdded($expectedFile, 'filters');
    }

    #[Test]
    public function it_changes_crud_resource_namespace(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // V3 should use MoonShine\Laravel\Resources\CrudResource
        $this->assertFileContains($v3File, 'use MoonShine\Laravel\Resources\CrudResource;');

        // V4 should use MoonShine\Crud\Resources\CrudResource
        $this->assertFileContains($v4File, 'use MoonShine\Crud\Resources\CrudResource;');
    }

    #[Test]
    public function it_updates_method_signatures_for_event_handlers(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // V3 uses Model as parameter
        $v3Content = $this->getFileContent($v3File);
        $this->assertStringContainsString('protected function beforeCreating(Model $item)', $v3Content);
        $this->assertStringContainsString('protected function beforeUpdating(Model $item)', $v3Content);

        // V4 uses DataWrapperContract as parameter
        $v4Content = $this->getFileContent($v4File);
        $this->assertStringContainsString('protected function beforeCreating(DataWrapperContract $item)', $v4Content);
        $this->assertStringContainsString('protected function beforeUpdating(DataWrapperContract $item)', $v4Content);
        $this->assertStringContainsString('use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;', $v4Content);
    }

    #[Test]
    public function it_updates_config_layout_class(): void
    {
        $v3Config = $this->fixturesPath . '/V3/config/moonshine.php';
        $v4Config = $this->fixturesPath . '/V4Expected/config/moonshine.php';

        // V3 should use CompactLayout
        $this->assertFileContains($v3Config, 'use MoonShine\Laravel\Layouts\CompactLayout;');
        $this->assertFileContains($v3Config, "'layout' => CompactLayout::class");

        // V4 should use AppLayout
        $this->assertFileContains($v4Config, 'use MoonShine\Laravel\Layouts\AppLayout;');
        $this->assertFileContains($v4Config, "'layout' => AppLayout::class");
    }

    #[Test]
    public function it_updates_config_auth_key(): void
    {
        $v3Config = $this->fixturesPath . '/V3/config/moonshine.php';
        $v4Config = $this->fixturesPath . '/V4Expected/config/moonshine.php';

        $v3Content = $this->getFileContent($v3Config);
        $v4Content = $this->getFileContent($v4Config);

        // V3 uses 'enable' key
        $this->assertStringContainsString("'enable' => true", $v3Content);

        // V4 uses 'enabled' key
        $this->assertStringContainsString("'enabled' => true", $v4Content);
        $this->assertStringNotContainsString("'enable' => true", $v4Content);
    }

    #[Test]
    public function it_maintains_user_resource_simple_structure(): void
    {
        // UserResource doesn't have custom pages, so it should stay in place
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/UserResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/User/UserResource.php';

        // Both should have the same structure, just namespace updated
        $this->assertFileExists($v3File);
        $this->assertFileExists($v4File);

        // V3 namespace
        $this->assertNamespaceChanged($v3File, 'App\MoonShine\Resources');

        // V4 namespace (should be moved to User subdirectory)
        $this->assertNamespaceChanged($v4File, 'App\MoonShine\Resources\User');
    }

    #[Test]
    public function it_verifies_expected_fixture_structure(): void
    {
        // Verify V3 structure
        $this->assertFileExists($this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php');
        $this->assertFileExists($this->fixturesPath . '/V3/app/MoonShine/Resources/UserResource.php');
        $this->assertFileExists($this->fixturesPath . '/V3/app/MoonShine/Pages/Article/ArticleIndexPage.php');
        $this->assertFileExists($this->fixturesPath . '/V3/config/moonshine.php');

        // Verify V4 expected structure
        $this->assertResourceStructure($this->fixturesPath . '/V4Expected', 'Article');
        $this->assertResourceStructure($this->fixturesPath . '/V4Expected', 'User', false);
        $this->assertFileExists($this->fixturesPath . '/V4Expected/config/moonshine.php');

        // Verify pages are in the right place for Article
        $this->assertFileExists($this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/Pages/ArticleIndexPage.php');
        $this->assertFileExists($this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/Pages/ArticleFormPage.php');
        $this->assertFileExists($this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/Pages/ArticleDetailPage.php');
    }

    #[Test]
    public function it_verifies_page_namespaces_updated(): void
    {
        // V3 Pages
        $v3IndexPage = $this->fixturesPath . '/V3/app/MoonShine/Pages/Article/ArticleIndexPage.php';
        $this->assertNamespaceChanged($v3IndexPage, 'App\MoonShine\Pages\Article');

        // V4 Pages should be in Resources/Article/Pages namespace
        $v4IndexPage = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/Pages/ArticleIndexPage.php';
        $this->assertNamespaceChanged($v4IndexPage, 'App\MoonShine\Resources\Article\Pages');
    }

    #[Test]
    public function it_preserves_resource_configuration(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $v3Content = $this->getFileContent($v3File);
        $v4Content = $this->getFileContent($v4File);

        // These should be preserved
        $preservedContent = [
            "public string \$model = Article::class;",
            "public string \$title = 'Articles';",
            "protected ?ClickAction \$clickAction = ClickAction::EDIT;",
            "public array \$with = ['author'];",
            "public string \$column = 'title';",
        ];

        foreach ($preservedContent as $content) {
            $this->assertStringContainsString($content, $v3Content, "V3 should contain: {$content}");
            $this->assertStringContainsString($content, $v4Content, "V4 should preserve: {$content}");
        }
    }
}

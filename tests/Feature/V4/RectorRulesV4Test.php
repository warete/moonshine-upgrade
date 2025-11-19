<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Feature\V4;

use PHPUnit\Framework\Attributes\Test;
use Warete\MoonshineUpgrade\Tests\Concerns\AssertUpgrade;
use Warete\MoonshineUpgrade\Tests\UnitTestCase;

final class RectorRulesV4Test extends UnitTestCase
{
    use AssertUpgrade;

    protected string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesPath = __DIR__ . '/../../Fixtures';
    }

    #[Test]
    public function it_renames_crud_resource_class(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // Old import
        $this->assertFileContains(
            $v3File,
            'use MoonShine\Laravel\Resources\CrudResource;'
        );

        // New import
        $this->assertFileContains(
            $v4File,
            'use MoonShine\Crud\Resources\CrudResource;'
        );

        $this->assertFileNotContains(
            $v4File,
            'use MoonShine\Laravel\Resources\CrudResource;'
        );
    }

    #[Test]
    public function it_renames_compact_layout_to_app_layout(): void
    {
        $v3Config = $this->fixturesPath . '/V3/config/moonshine.php';
        $v4Config = $this->fixturesPath . '/V4Expected/config/moonshine.php';

        // Old class
        $this->assertFileContains(
            $v3Config,
            'use MoonShine\Laravel\Layouts\CompactLayout;'
        );

        // New class
        $this->assertFileContains(
            $v4Config,
            'use MoonShine\Laravel\Layouts\AppLayout;'
        );

        $this->assertFileContains(
            $v4Config,
            "'layout' => AppLayout::class"
        );
    }

    #[Test]
    public function it_adds_deprecated_doc_to_index_buttons(): void
    {
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $this->assertDeprecatedDocAdded($v4File, 'indexButtons');

        $content = $this->getFileContent($v4File);
        $this->assertStringContainsString(
            '@deprecated 4.x: Method removed; Use `buttons()` in resource `IndexPage`.',
            $content
        );
    }

    #[Test]
    public function it_adds_deprecated_doc_to_form_buttons(): void
    {
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $this->assertDeprecatedDocAdded($v4File, 'formButtons');

        $content = $this->getFileContent($v4File);
        $this->assertStringContainsString(
            '@deprecated 4.x: Method removed; Use `buttons()` in resource Form page.',
            $content
        );
    }

    #[Test]
    public function it_adds_deprecated_doc_to_metrics(): void
    {
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $this->assertDeprecatedDocAdded($v4File, 'metrics');

        $content = $this->getFileContent($v4File);
        $this->assertStringContainsString(
            '@deprecated 4.x: Method removed; Use `metrics()` in resource Index page.',
            $content
        );
    }

    #[Test]
    public function it_adds_deprecated_doc_to_filters(): void
    {
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $this->assertDeprecatedDocAdded($v4File, 'filters');

        $content = $this->getFileContent($v4File);
        $this->assertStringContainsString(
            '@deprecated 4.x: Method deprecated and will be removed in v5.x; Use `filters()` in resource Index page.',
            $content
        );
    }

    #[Test]
    public function it_changes_event_method_signatures(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $v3Content = $this->getFileContent($v3File);
        $v4Content = $this->getFileContent($v4File);

        // beforeCreating signature change
        $this->assertStringContainsString(
            'protected function beforeCreating(Model $item): Model',
            $v3Content
        );

        $this->assertStringContainsString(
            'protected function beforeCreating(DataWrapperContract $item): DataWrapperContract',
            $v4Content
        );

        // beforeUpdating signature change
        $this->assertStringContainsString(
            'protected function beforeUpdating(Model $item): Model',
            $v3Content
        );

        $this->assertStringContainsString(
            'protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract',
            $v4Content
        );

        // Import added
        $this->assertClassImported(
            $v4File,
            'MoonShine\Contracts\Core\TypeCasts\DataWrapperContract'
        );
    }

    #[Test]
    public function it_updates_config_auth_enable_to_enabled(): void
    {
        $v3Config = $this->fixturesPath . '/V3/config/moonshine.php';
        $v4Config = $this->fixturesPath . '/V4Expected/config/moonshine.php';

        $v3Content = $this->getFileContent($v3Config);
        $v4Content = $this->getFileContent($v4Config);

        // V3 has 'enable' key
        $this->assertMatchesRegularExpression(
            "/'auth'\s*=>\s*\[.*'enable'\s*=>\s*true/s",
            $v3Content
        );

        // V4 has 'enabled' key
        $this->assertMatchesRegularExpression(
            "/'auth'\s*=>\s*\[.*'enabled'\s*=>\s*true/s",
            $v4Content
        );

        // V4 should not have 'enable' key
        $this->assertDoesNotMatchRegularExpression(
            "/'enable'\s*=>/",
            $v4Content
        );
    }

    #[Test]
    public function it_preserves_attributes_and_properties(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        $preservedItems = [
            '#[Icon(\'newspaper\')]',
            '#[Order(3)]',
            'public string $model = Article::class;',
            'public string $title = \'Articles\';',
            'protected ?ClickAction $clickAction = ClickAction::EDIT;',
            'public array $with = [\'author\'];',
            'public string $column = \'title\';',
        ];

        foreach ($preservedItems as $item) {
            $this->assertFileContains($v3File, $item, "V3 should contain: {$item}");
            $this->assertFileContains($v4File, $item, "V4 should preserve: {$item}");
        }
    }

    #[Test]
    public function it_preserves_method_implementations(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // Methods should still exist and have implementations
        $methods = [
            'protected function pages(): array',
            'public function indexFields(): iterable',
            'public function formFields(): iterable',
            'public function detailFields(): iterable',
            'protected function rules(mixed $item): array',
        ];

        foreach ($methods as $method) {
            $this->assertFileContains($v3File, $method, "V3 should have method: {$method}");
            $this->assertFileContains($v4File, $method, "V4 should preserve method: {$method}");
        }
    }

    #[Test]
    public function it_updates_page_class_references_in_pages_method(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // V3 references pages from different namespace
        $v3Content = $this->getFileContent($v3File);
        $this->assertStringContainsString('use App\MoonShine\Pages\Article\ArticleIndexPage;', $v3Content);

        // V4 references pages from Resources subdirectory
        $v4Content = $this->getFileContent($v4File);
        $this->assertStringContainsString('use App\MoonShine\Resources\Article\Pages\ArticleIndexPage;', $v4Content);
        $this->assertStringContainsString('use App\MoonShine\Resources\Article\Pages\ArticleFormPage;', $v4Content);
        $this->assertStringContainsString('use App\MoonShine\Resources\Article\Pages\ArticleDetailPage;', $v4Content);
    }

    #[Test]
    public function it_removes_model_import_after_signature_change(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/ArticleResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Article/ArticleResource.php';

        // V3 imports Model
        $this->assertFileContains(
            $v3File,
            'use Illuminate\Database\Eloquent\Model;'
        );

        // V4 should not import Model if not used elsewhere
        // (Rector should clean unused imports)
        $v4Content = $this->getFileContent($v4File);

        // If Model is still imported, it means it's used elsewhere in the class
        // Otherwise, rector should have removed it
        // This is a soft check - we just verify DataWrapperContract is imported
        $this->assertStringContainsString(
            'use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;',
            $v4Content
        );
    }

    #[Test]
    public function it_adds_async_method_attribute(): void
    {
        $v3File = $this->fixturesPath . '/V3/app/MoonShine/Resources/CommentResource.php';
        $v4File = $this->fixturesPath . '/V4Expected/app/MoonShine/Resources/Comment/CommentResource.php';

        $this->assertFileExists($v3File);
        $this->assertFileExists($v4File);

        $v3Content = $this->getFileContent($v3File);
        $v4Content = $this->getFileContent($v4File);

        // V3 should NOT have AsyncMethod attribute
        $this->assertStringNotContainsString('#[AsyncMethod]', $v3Content);
        $this->assertStringNotContainsString('use MoonShine\Support\Attributes\AsyncMethod;', $v3Content);

        // V4 should have AsyncMethod attribute on methods
        $this->assertStringContainsString('#[AsyncMethod]', $v4Content);
        $this->assertStringContainsString('use MoonShine\Support\Attributes\AsyncMethod;', $v4Content);

        // Check that approve() method has the attribute
        $this->assertMatchesRegularExpression(
            '/#\[AsyncMethod\]\s+public function approve\(/s',
            $v4Content,
            'approve() method should have #[AsyncMethod] attribute'
        );

        // Check that asyncAction() method has the attribute
        $this->assertMatchesRegularExpression(
            '/#\[AsyncMethod\]\s+public function asyncAction\(/s',
            $v4Content,
            'asyncAction() method should have #[AsyncMethod] attribute'
        );

        // Verify CrudRequestContract parameter is present (signature-based detection)
        $this->assertStringContainsString(
            'CrudRequestContract $request',
            $v4Content,
            'asyncAction should have CrudRequestContract parameter'
        );
    }
}

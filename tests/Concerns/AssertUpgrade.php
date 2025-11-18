<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Concerns;

use Illuminate\Support\Facades\File;

trait AssertUpgrade
{
    protected function copyFixtures(string $from, string $to): void
    {
        if (File::exists($to)) {
            File::deleteDirectory($to);
        }

        File::copyDirectory($from, $to);
    }

    protected function assertUpgradeFileExists(string $path, string $message = ''): void
    {
        $this->assertTrue(
            File::exists($path),
            $message ?: "Failed asserting that file exists at: {$path}"
        );
    }

    protected function assertUpgradeFileNotExists(string $path, string $message = ''): void
    {
        $this->assertFalse(
            File::exists($path),
            $message ?: "Failed asserting that file does not exist at: {$path}"
        );
    }

    protected function assertUpgradeDirectoryExists(string $path, string $message = ''): void
    {
        $this->assertTrue(
            File::isDirectory($path),
            $message ?: "Failed asserting that directory exists at: {$path}"
        );
    }

    protected function assertFileContains(string $path, string $needle, string $message = ''): void
    {
        $this->assertUpgradeFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString(
            $needle,
            $content,
            $message ?: "Failed asserting that file {$path} contains: {$needle}"
        );
    }

    protected function assertFileNotContains(string $path, string $needle, string $message = ''): void
    {
        $this->assertUpgradeFileExists($path);

        $content = File::get($path);
        $this->assertStringNotContainsString(
            $needle,
            $content,
            $message ?: "Failed asserting that file {$path} does not contain: {$needle}"
        );
    }

    protected function assertNamespaceChanged(string $path, string $expectedNamespace): void
    {
        $this->assertUpgradeFileExists($path);
        $this->assertFileContains($path, "namespace {$expectedNamespace};");
    }

    protected function assertClassImported(string $path, string $expectedImport): void
    {
        $this->assertUpgradeFileExists($path);
        $this->assertFileContains($path, "use {$expectedImport};");
    }

    protected function assertDeprecatedDocAdded(string $path, string $methodName): void
    {
        $this->assertUpgradeFileExists($path);
        $content = File::get($path);

        // Check if the method exists and has @deprecated tag
        $this->assertMatchesRegularExpression(
            '/\*\s*@deprecated.*\*\/\s*public function ' . preg_quote($methodName, '/') . '/s',
            $content,
            "Failed asserting that method {$methodName} has @deprecated doc in {$path}"
        );
    }

    protected function assertResourceStructure(string $basePath, string $resourceName): void
    {
        $resourceDir = "{$basePath}/app/MoonShine/Resources/{$resourceName}";
        $this->assertUpgradeDirectoryExists($resourceDir);
        $this->assertUpgradeFileExists("{$resourceDir}/{$resourceName}Resource.php");
        $this->assertUpgradeDirectoryExists("{$resourceDir}/Pages");
    }

    protected function getFileContent(string $path): string
    {
        $this->assertUpgradeFileExists($path);
        return File::get($path);
    }

    protected function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    protected function assertFilesAreSimilar(string $expectedPath, string $actualPath, array $ignorePatterns = []): void
    {
        $this->assertUpgradeFileExists($expectedPath);
        $this->assertUpgradeFileExists($actualPath);

        $expected = $this->normalizeLineEndings(File::get($expectedPath));
        $actual = $this->normalizeLineEndings(File::get($actualPath));

        // Remove patterns to ignore
        foreach ($ignorePatterns as $pattern) {
            $expected = preg_replace($pattern, '', $expected);
            $actual = preg_replace($pattern, '', $actual);
        }

        $this->assertEquals(
            trim($expected),
            trim($actual),
            "Files are not similar:\nExpected: {$expectedPath}\nActual: {$actualPath}"
        );
    }
}

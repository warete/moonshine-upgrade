# Moonshine upgrade


A small package that helps automate migration of a MoonShine application to a newer major version. It runs automated code transformations and configuration updates to prepare your codebase for an upgrade. Use the `--dry-run` option to preview changes before they are applied.

## Compatibility

| MoonShine | moonshine-upgrade | Currently supported |
|:---------:|:-----------------:|:-------------------:|
|  \>= 3.0  |        1.*        |         yes         |

## Install

**Make this before upgrade moonshine version in `composer.json`:**

1. `composer require --dev warete/moonshine-upgrade`
2. `php artisan vendor:publish --provider="Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider"`
3. `php artisan moonshine:upgrade <version>`. By default will used last available version.
4. Then upgrade moonshine version in `composer.json` and run `composer update`!

You can specify directory to upgrade by `--dir` option. For example: `php artisan moonshine:upgrade --dir=app/MoonShine/Resources`.


> **Warning**
>
>The script performs automated edits to your codebase — files will be changed, and some files may be moved, renamed or removed by the upgrade process. Always make a backup (or use VCS) before running the upgrade. You can use the command option `--dry-run` to preview changes without writing files.
>
>Use the `-v` flag to see which files were modified and view their diffs.

The upgrade command can be run multiple times — it will update only the outdated files.

> **Note — manual follow-up required**
>
> Deprecated methods, classes and class properties will receive an automatically added PHPDoc containing an `@deprecated` tag and a short guidance on what needs to be changed manually. After running the automated upgrade you must review all members marked with `@deprecated` and apply the instructions in those comments — automated refactoring cannot safely handle all cases and skipping this step may cause your application to behave unexpectedly.

## Supported versions to upgrade

- 4.* — current MoonShine major version supported.

  - Method signatures, namespaces, classes, interfaces, traits, properties and attributes changed.
  - Resources and their pages will be adapted to the new structure.
  - The `config/moonshine.php` configuration will be updated to support the latest features.


## Testing

This package includes two types of tests:

### Unit Tests (No MoonShine required) ✅
Run lightweight tests that validate Rector rules and fixtures without requiring a full MoonShine installation:

```bash
composer test:unit
# or
composer test:v4
```

These tests will always pass in CI/CD and during package development.

### Full E2E Tests (Requires MoonShine)
To run the complete test suite including end-to-end upgrade tests:

1. Install MoonShine manually: `composer require moonshine/moonshine:^4.0 --dev`
2. Run all tests: `composer test:all`

**Note**: MoonShine is intentionally not in `require-dev` to avoid version conflicts when this package is used within MoonShine applications. The E2E tests are primarily for development and validation in isolated environments.

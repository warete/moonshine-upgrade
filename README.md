# Moonshine upgrade


A small package that helps automate migration of a MoonShine application to a newer major version. It runs automated code transformations and configuration updates to prepare your codebase for an upgrade. Use the `--dry-run` option to preview changes before they are applied.

## Compatibility

| MoonShine | moonshine-upgrade | Currently supported |
|:---------:|:-----------------:|:-------------------:|
|  \>= 3.0  |        3.*        |         yes         |

## Install

**Make this before upgrade moonshine version in `composer.json`:**

1. `composer require warete/moonshine-upgrade`
2. Add `Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider::class` to `bootstrap/providers.php`
3. `php artisan vendor:publish --provider="Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider"`
4. `php artisan moonshine:upgrade <version>`. Available versions: `4`
5. Add `Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider::class` from `bootstrap/providers.php`

Then upgrade moonshine version in `composer.json` and run `composer update`!

> **Note — manual follow-up required**
>
> Deprecated methods, classes and class properties will receive an automatically added PHPDoc containing an `@deprecated` tag and a short guidance on what needs to be changed manually. After running the automated upgrade you must review all members marked with `@deprecated` and apply the instructions in those comments — automated refactoring cannot safely handle all cases and skipping this step may cause your application to behave unexpectedly.

## Supported versions to upgrade

- 4.* — current MoonShine major version supported.

  - Method signatures, namespaces, classes, interfaces, traits, properties and attributes changed.
  - Resources and their pages will be adapted to the new structure.
  - The `config/moonshine.php` configuration will be updated to support the latest features.

Note: the script performs automated edits to your codebase — files will be changed, and some files may be moved, renamed or removed by the upgrade process. Always make a backup (or use VCS) before running the upgrade. You can use the command option `--dry-run` to preview changes without writing files.


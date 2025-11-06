# Moonshine upgrade

## Install

**Make this before upgrade moonshine version in `composer.json`:**

1. `composer require warete/moonshine-upgrade`
2. Add `Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider::class` to `bootstrap/providers.php`
3. `php artisan vendor:publish --provider="Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider"`
4. `php artisan moonshine:upgrade <version>`. Available versions: `4`
5. Add `Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider::class` from `bootstrap/providers.php`

Then upgrade moonshine version in `composer.json` and run `composer update`!

todo: write about marked as `deprecated` methods and what need to do with it

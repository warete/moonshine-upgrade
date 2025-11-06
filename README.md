# Moonshine upgrade

## Install

**Make this before upgrade moonshine version in `composer.json`:**

1. `composer require warete/moonshine-upgrade`
2. `php artisan vendor:publish --provider="Warete\MoonshineUpgrade\Providers\MoonshineUpgradeServiceProvider"`
3. `php artisan moonshine:upgrade <version>`. Available versions: `4`

Then upgrade moonshine version in `composer.json` and run `composer update`!

todo: write about marked as `deprecated` methods and what need to do with it

<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/app',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/vendor',
        __DIR__ . '/stubs',
        __DIR__ . '/tests',
        ExplicitBoolCompareRector::class,
        FirstClassCallableRector::class,
        CompleteDynamicPropertiesRector::class => __DIR__ . '/src/VersionStrategies/V4.php',
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->removeUnusedImports();

    $rectorConfig->rule(ExplicitNullableParamTypeRector::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);
};

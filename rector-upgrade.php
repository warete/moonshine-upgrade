<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/routes',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/resources',
        __DIR__ . '/database',
        __DIR__ . '/vendor',
    ]);

    $rectorConfig->ruleWithConfiguration(
        RenameClassRector::class,
        [
            'MoonShine\\Laravel\\MoonShineRequest' => 'MoonShine\\Contracts\\Core\\DependencyInjection\\CrudRequestContract',
            'MoonShine\\Laravel\\Http\\Responses\\MoonShineJsonResponse' => 'MoonShine\\Crud\\JsonResponse',
            'MoonShine\\Laravel\\Enums\\Action' => 'MoonShine\\Support\\Enums\\Action',
            'MoonShine\Laravel\Forms\FiltersForm' => 'MoonShine\Crud\Forms\FiltersForm',
            'MoonShine\Laravel\Forms\LoginForm' => 'MoonShine\Crud\Forms\LoginForm',
            'MoonShine\Laravel\Traits\WithComponentsPusher' => 'MoonShine\Crud\Traits\WithComponentsPusher',
            'MoonShine\UI\Fields\StackFields' => 'MoonShine\UI\Fields\Fieldset',
        ],
    );

    $allowedBuilders = [
        'MoonShine\\UI\\Components\\ActionButton',
        'MoonShine\\UI\Components\\FormBuilder',
        'MoonShine\\Advanced\\Components\\Tabs\\AsyncTab',
    ];

    $attributeFqcn = 'MoonShine\\Support\\Attributes\\AsyncMethod';

    $rectorConfig->ruleWithConfiguration(\Warete\MoonshineUpgrade\Rector\AddAsyncMethodAttributeRector::class, [
        'attributeFqcn' => $attributeFqcn,
        'allowedBuilderClasses' => $allowedBuilders,
    ]);

    $rectorConfig->ruleWithConfiguration(\Warete\MoonshineUpgrade\Rector\RemoveConfiguredMethodPairsRector::class, [
        ['MoonShine\\Laravel\\DependencyInjection\\MoonShineConfigurator', 'authDisable'],
        ['MoonShine\\Laravel\\DependencyInjection\\MoonShineConfigurator', 'authEnable'],
    ]);

    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

};

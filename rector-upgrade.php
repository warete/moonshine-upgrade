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
            'MoonShine\\Laravel\\Forms\\FiltersForm' => 'MoonShine\\Crud\\Forms\\FiltersForm',
            'MoonShine\\Laravel\\Forms\\LoginForm' => 'MoonShine\\Crud\\Forms\\LoginForm',
            'MoonShine\Laravel\Traits\WithComponentsPusher' => 'MoonShine\\Crud\\Traits\\WithComponentsPusher',
            'MoonShine\\UI\\Fields\\StackFields' => 'MoonShine\\UI\\Fields\\Fieldset',
            'MoonShine\\Laravel\\Layouts\\CompactLayout' => 'MoonShine\\Laravel\\Layouts\\AppLayout',
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

    $rectorConfig->ruleWithConfiguration(\Warete\MoonshineUpgrade\Rector\ReorderConfiguredMethodArgsRector::class, [
        [
            'class'      => 'MoonShine\\MenuManager\\MenuItem',
            'method'     => 'make',
            'call_types' => 'both',
            'swap'       => [0, 1],
        ],
    ]);

    $rectorConfig->ruleWithConfiguration(\Warete\MoonshineUpgrade\Rector\AddDeprecatedDocToMembersRector::class, [
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'property'=> 'clickAction',
            'message' => '4.x: Property removed; Modify `TableBuilder` component in resource `IndexPage` via `modifyListComponent()`.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'indexButtons',
            'message' => '4.x: Method removed; Use `buttons()` in resource `IndexPage`.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'topButtons',
            'message' => '4.x: Method removed; Use `topLeftButtons()` or `topRightButtons()` in resource pages.',
        ],
    ]);

    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

};

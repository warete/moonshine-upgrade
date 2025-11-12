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
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'handlers',
            'message' => '4.x: Method removed; Use `handlers()` in resource pages.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'metrics',
            'message' => '4.x: Method removed; Use `metrics()` in resource Index page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'modifyFormComponent',
            'message' => '4.x: Method removed; Use `modifyFormComponent()` in resource Form page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'formButtons',
            'message' => '4.x: Method removed; Use `buttons()` in resource Form page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'formBuilderButtons',
            'message' => '4.x: Method removed; Use `formButtons()` in resource Form page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'thead',
            'message' => '4.x: Method removed.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'tbody',
            'message' => '4.x: Method removed.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'tfoot',
            'message' => '4.x: Method removed.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'modifyListComponent',
            'message' => '4.x: Method removed; Use `modifyListComponent()` in resource Index page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'queryTags',
            'message' => '4.x: Method deprecated and will be removed in v5.x; Use `queryTags()` in resource Index page.',
        ],
        [
            'class'   => 'MoonShine\\Laravel\\Resources\\CrudResource',
            'method'=> 'filters',
            'message' => '4.x: Method deprecated and will be removed in v5.x; Use `filters()` in resource Index page.',
        ],
    ]);

    $rectorConfig->ruleWithConfiguration(\Warete\MoonshineUpgrade\Rector\ChangeMethodSignatureRector::class, [
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'beforeCreating',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'afterCreated',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'beforeUpdating',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'afterUpdated',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'beforeDeleting',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
        [
            'class' => 'MoonShine\\Laravel\\Traits\\Resource\\ResourceEvents',
            'method' => 'afterDeleted',
            'params' => [
                0 => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
            ],
            'return' => 'MoonShine\\Contracts\\Core\\TypeCasts\\DataWrapperContract',
        ],
    ]);

    $rectorConfig->ruleWithConfiguration(
        RenameClassRector::class,
        [
            'MoonShine\\Laravel\\MoonShineRequest' => 'MoonShine\\Contracts\\Core\\DependencyInjection\\CrudRequestContract',
            'MoonShine\\Laravel\\Http\\Responses\\MoonShineJsonResponse' => 'MoonShine\\Crud\\JsonResponse',
            'MoonShine\\Laravel\\Enums\\Action' => 'MoonShine\\Support\\Enums\\Action',
            'MoonShine\\Laravel\\Enums\\Ability' => 'MoonShine\\Support\\Enums\\Ability',
            'MoonShine\Laravel\Traits\WithComponentsPusher' => 'MoonShine\\Crud\\Traits\\WithComponentsPusher',
            'MoonShine\\Laravel\\Layouts\\CompactLayout' => 'MoonShine\\Laravel\\Layouts\\AppLayout',
            'MoonShine\\Laravel\\Resources\\CrudResource' => 'MoonShine\\Crud\\Resources\\CrudResource',
            'MoonShine\\Laravel\\Contracts\\Notifications\\MoonShineNotificationContract' => 'MoonShine\\Crud\\Contracts\\Notifications\\MoonShineNotificationContract',
            #forms
            'MoonShine\\Laravel\\Forms\\FiltersForm' => 'MoonShine\\Crud\\Forms\\FiltersForm',
            'MoonShine\\Laravel\\Forms\\LoginForm' => 'MoonShine\\Crud\\Forms\\LoginForm',
            //components
            'MoonShine\\Laravel\\Components\\Fragment' => 'MoonShine\\Crud\\Components\\Fragment',
            'MoonShine\\Laravel\\Components\\Paginator' => 'MoonShine\\Crud\\Components\\Paginator',
            'MoonShine\Laravel\Components\Layout\Locales' => 'MoonShine\\Crud\\Components\\Layout\\Locales',
            'MoonShine\Laravel\Components\Layout\Notifications' => 'MoonShine\\Crud\\Components\\Layout\\Notifications',
            'MoonShine\Laravel\Components\Layout\Search' => 'MoonShine\\Crud\\Components\\Layout\\Search',
            'MoonShine\\UI\\Fields\\StackFields' => 'MoonShine\\UI\\Fields\\Fieldset',
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

    $rectorConfig->rule(\Warete\MoonshineUpgrade\Rector\MoonShineConfigUpdateRule::class);
    $rectorConfig->removeUnusedImports();
    $rectorConfig->rule(\Warete\MoonshineUpgrade\Rector\ImportShortClassReferencesRector::class);
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};

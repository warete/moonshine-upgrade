<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    $rectorConfig->ruleWithConfiguration(
        RenameClassRector::class,
        [
            'MoonShine\\Laravel\\MoonShineRequest' => 'MoonShine\\Contracts\\Core\\DependencyInjection\\CrudRequestContract',
            'MoonShine\\Laravel\\MoonShineJsonResponse' => 'MoonShine\\Crud\\JsonResponse',
            'MoonShine\\Laravel\\Http\\Responses\\MoonShineJsonResponse' => 'MoonShine\\Crud\\JsonResponse',
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

};

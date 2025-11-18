<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Comment;

use MoonShine\Crud\Resources\CrudResource;
use MoonShine\Contracts\UI\Crud\CrudRequestContract;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;

class CommentResource extends CrudResource
{
    public string $model = Comment::class;
    
    public string $title = 'Comments';

    public function indexFields(): iterable
    {
        return [];
    }

    public function formFields(): iterable
    {
        return [];
    }

    public function detailFields(): iterable
    {
        return [];
    }

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        return $item;
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        return $item;
    }

    // Async methods with AsyncMethod attributes in V4
    #[AsyncMethod]
    public function approve(): void
    {
        // async approve action
    }

    #[AsyncMethod]
    public function asyncAction(CrudRequestContract $request): void
    {
        // async action with CrudRequestContract parameter
    }
}

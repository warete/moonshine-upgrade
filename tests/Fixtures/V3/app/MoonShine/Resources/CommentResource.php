<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\CrudResource;

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

    protected function beforeCreating(Model $item): Model
    {
        return $item;
    }

    protected function beforeUpdating(Model $item): Model
    {
        return $item;
    }

    // Async methods without attributes in V3
    public function approve(): void
    {
        // async approve action
    }

    public function asyncAction($request): void
    {
        // async action
    }
}

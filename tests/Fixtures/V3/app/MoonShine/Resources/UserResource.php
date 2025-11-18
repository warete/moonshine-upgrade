<?php

namespace App\MoonShine\Resources;

use App\Models\User;
use MoonShine\Laravel\Resources\CrudResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

#[Icon('user')]
#[Order(4)]
class UserResource extends CrudResource
{
    protected string $model = User::class;

    protected string $title = 'Users';

    protected string $column = 'name';

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Name'),
            Email::make('E-mail', 'email'),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Name'),
            Email::make('E-mail', 'email'),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ...$this->indexFields(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'name' => 'required',
            'email' => 'sometimes|bail|required|email|unique:users,email' . ($item->exists ? ",$item->id" : ''),
        ];
    }
}

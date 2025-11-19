<?php

namespace App\MoonShine\Resources;

use App\Models\Article;
use App\MoonShine\Pages\Article\ArticleDetailPage;
use App\MoonShine\Pages\Article\ArticleFormPage;
use App\MoonShine\Pages\Article\ArticleIndexPage;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\CrudResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\ClickAction;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

#[Icon('newspaper')]
#[Order(3)]
class ArticleResource extends CrudResource
{
    public string $model = Article::class;

    public string $title = 'Articles';

    protected ?ClickAction $clickAction = ClickAction::EDIT;

    public array $with = ['author'];

    public string $column = 'title';

    protected function pages(): array
    {
        return [
            ArticleIndexPage::class,
            ArticleFormPage::class,
            ArticleDetailPage::class,
        ];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Title'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Title'),
        ];
    }

    public function detailFields(): iterable
    {
        return $this->indexFields();
    }

    public function indexButtons(): array
    {
        return [
            ...parent::indexButtons(),
        ];
    }

    public function formButtons(): array
    {
        return parent::formButtons();
    }

    public function metrics(): array
    {
        return [];
    }

    public function filters(): iterable
    {
        return [
            Text::make('Title'),
        ];
    }

    protected function beforeCreating(Model $item): Model
    {
        return $item;
    }

    protected function beforeUpdating(Model $item): Model
    {
        return $item;
    }

    protected function rules(mixed $item): array
    {
        return [
            'title' => ['required', 'string', 'min:2'],
        ];
    }
}

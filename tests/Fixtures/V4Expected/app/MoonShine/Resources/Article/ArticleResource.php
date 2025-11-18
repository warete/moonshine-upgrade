<?php

namespace App\MoonShine\Resources\Article;

use App\Models\Article;
use App\MoonShine\Resources\Article\Pages\ArticleDetailPage;
use App\MoonShine\Resources\Article\Pages\ArticleFormPage;
use App\MoonShine\Resources\Article\Pages\ArticleIndexPage;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Crud\Resources\CrudResource;
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

    /**
     * @deprecated 4.x: Method removed; Use `buttons()` in resource `IndexPage`.
     */
    public function indexButtons(): array
    {
        return [
            ...parent::indexButtons(),
        ];
    }

    /**
     * @deprecated 4.x: Method removed; Use `buttons()` in resource Form page.
     */
    public function formButtons(): array
    {
        return parent::formButtons();
    }

    /**
     * @deprecated 4.x: Method removed; Use `metrics()` in resource Index page.
     */
    public function metrics(): array
    {
        return [];
    }

    /**
     * @deprecated 4.x: Method deprecated and will be removed in v5.x; Use `filters()` in resource Index page.
     */
    public function filters(): iterable
    {
        return [
            Text::make('Title'),
        ];
    }

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        return $item;
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
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

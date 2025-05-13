<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\News;

use Illuminate\Support\Carbon;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Url;

#[Icon('newspaper')]

/**
 * @extends ModelResource<News>
 */
class NewsResource extends ModelResource
{
    protected string $model = News::class;

    protected string $title = 'News';

    protected string $column = 'title';

    protected bool $detailInModal = true;

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Image::make('Изображение', 'image')->disk('public')->dir('news_images')->nullable(),
            Text::make('Заголовок', 'title'),
            Date::make('Дата создания', 'created_at')->sortable()->format('Y-m-d'),
        ];
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            Box::make([
                Text::make('Заголовок', 'title')->required(),
                Textarea::make('Содержание', 'short_description')->required(),
                Url::make('Ссылка на источник', 'link')->required(),
                Image::make('Изображение', 'image')->disk('public')->dir('news_images')->required(),
            ])
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Заголовок', 'title'),
            Textarea::make('Содержание', 'short_description'),
            Url::make('Ссылка на источник', 'link'),
            Image::make('Изображение', 'image'),
            Date::make('Дата создания', 'created_at'),
            Date::make('Последнее обновление', 'updated_at'),
        ];
    }

    /**
     * @param News $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'link' => 'required|url|max:255',
            'image' => 'required|image|max:2048',
        ];
    }

    protected function afterCreated(mixed $item): mixed
    {
        $this->cleanupNews();
        return null;
    }

    private function cleanupNews(): void
    {
        $newsCount = News::count();
        $threshold = 10;

        if ($newsCount > $threshold) {
            $itemsToDelete = $newsCount - $threshold;

            $oldestNews = News::orderBy('created_at', 'asc')
            ->take($itemsToDelete)
            ->get();

            foreach ($oldestNews as $news) {
                $news->delete();
            }
        }
    }
}
